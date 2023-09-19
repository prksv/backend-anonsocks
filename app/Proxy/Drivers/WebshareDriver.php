<?php

namespace App\Proxy\Drivers;

use App\Connectors\Webshare;
use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyStatus;
use App\Enums\Proxy\ProxyType;
use App\Enums\Proxy\WebshareAccountType;
use App\Exceptions\CustomException;
use App\Models\Proxy;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RecaptchaV2Proxyless;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class WebshareDriver extends Driver
{
    private static ProxyProvider $provider_id = ProxyProvider::WEBSHARE;
    protected ProxyType $proxyType;
    private Webshare $api;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->proxyType(ProxyType::IPV4_SHARED);
    }

    /**
     * @throws \Exception
     */
    public function proxyType(ProxyType $proxyType): self
    {
        $accountType = match ($proxyType) {
            ProxyType::IPV4_PREMIUM => WebshareAccountType::PREMIUM,
            ProxyType::IPV4_SHARED => WebshareAccountType::DEFAULT,
            ProxyType::IPV4_SHARED_FREE => throw new \Exception(
                "This driver does not support {$proxyType->name}"
            ),
        };

        $this->proxyType = $proxyType;
        $this->api = new Webshare($accountType);

        return $this;
    }

    /**
     * @throws GuzzleException|\Throwable
     */
    public function getProxies(string $country_code, int $count): Collection
    {
        $proxies = Proxy::available()
            ->whereProviderAndType(self::$provider_id, $this->proxyType)
            ->where("country", $country_code)
            ->limit($count)
            ->get();
        Log::debug(json_encode([$proxies]));
        if ($proxies->count() >= $count) {
            return $proxies;
        }

        $countries = [$country_code => $count - $proxies->count()];

        $pricing = $this->api->getPrice($countries);

        if ($pricing["paid_today"] >= 2) {
            return $proxies->merge($this->buy($countries));
        }

        $priority_proxies = Proxy::fromPriorityPool()->get();

        $other_proxies = Proxy::available()
            ->whereNotIn("id", $priority_proxies->merge($proxies))
            ->get();

        $to_replace = $priority_proxies->merge($other_proxies);

        $available_replaces = min(
            $this->api->plan["proxy_replacements_available"],
            array_sum($countries)
        );

        $replaced_proxies = $this->makeReplace(
            $to_replace
                ->take($available_replaces)
                ->pluck("ip")
                ->all(),
            [
                $country_code => $available_replaces,
            ]
        );

        $proxies = $proxies->merge($replaced_proxies);

        if ($proxies->count() <= $count) {
            $proxies = $proxies->merge($this->buy([$country_code => $count - $proxies->count()]));
        }

        return $proxies;
    }

    /**
     * @throws GuzzleException
     * @throws \Throwable
     */
    private function buy(array $countries): Collection
    {
        $minimum_count = floor(1 / $this->api->getPerProxyPrice());
        $required_count = array_sum($countries);

        if ($required_count < $minimum_count) {
            $countries["ZZ"] = $minimum_count - $required_count;
        }

        $payment = $this->api->buyProxies(
            $countries,
            $this->api->getSubscription()["payment_method"],
            $this->solveCaptcha()
        );

        if ($payment["payment_required"]) {
            $this->confirmPayment(
                $payment["stripe_payment_intent"],
                $payment["stripe_payment_method"],
                $payment["stripe_client_secret"]
            );
            $pending_payment = $this->api->pendingPayment($payment["pending_payment"]);
            $this->api->processPayment($payment["pending_payment"], $pending_payment);
        }

        $exists_results = Proxy::whereIn("country", array_keys($countries))->get();

        $this->sync();

        $results_after_sync = Proxy::whereIn("country", array_keys($countries))->get();

        return $results_after_sync->diff($exists_results);
    }

    /**
     * @throws \Throwable
     */
    private function solveCaptcha()
    {
        $api = new RecaptchaV2Proxyless();
        $api->setKey(config("anticaptcha.api_key"));
        $api->setWebsiteURL("https://proxy2.webshare.io/subscription/customize");
        $api->setWebsiteKey(config("webshare.recaptcha_key"));

        throw_if(!$api->createTask(), new CustomException($api->getErrorMessage()));

        $result = $api->waitForResult();

        throw_if(!$result, new CustomException($api->getErrorMessage()));

        return $api->getTaskSolution();
    }

    /**
     * @throws ApiErrorException
     */
    private function confirmPayment(
        string $payment_intent,
        string $payment_method,
        string $client_secret
    ): void {
        $stripe = new StripeClient(config("webshare.stripe_key"));
        try {
            $stripe->paymentIntents->confirm(
                $payment_intent,
                compact("payment_method", "client_secret")
            );
        } catch (ApiErrorException $e) {
            return;
        }
    }

    /**
     * Делает замену прокси на указаннные страны
     *
     * @param array $ip_addresses
     * @param int[] $replace_countries
     * @return Collection Список заменённых прокси
     * @throws GuzzleException
     */
    public function makeReplace(array $ip_addresses = [], array $replace_countries = []): Collection
    {
        $requestId = $this->api->requestReplacement($ip_addresses, $replace_countries);
        while (empty(($response = $this->api->getReplacedProxy($requestId)))) {
            sleep(3);
        }
        $proxies = collect();
        foreach ($response as $replaced) {
            $proxy = Proxy::firstWhere("ip", $replaced["proxy"]);
            $replacedProxy = $proxy->replicate();

            $replacedProxy->ip = $replaced["replaced_with"];
            $replacedProxy->port = $replaced["replaced_with_port"];
            $replacedProxy->country = $replaced["replaced_with_country_code"];

            $proxy->update(["status" => ProxyStatus::REPLACED]);

            $replacedProxy->save();

            $proxies = $proxies->merge([$replacedProxy]);
        }
        return $proxies;
    }

    /**
     * @throws GuzzleException
     */
    protected function getAllProxies(): array
    {
        $result = [];

        $res = $this->api->getProxiesList(null, 100, 1);
        for ($page = 1; $page <= ceil($res["count"] / 100); $page++) {
            if ($page != 1) {
                $res = $this->api->getProxiesList(null, 100, $page);
            }
            foreach ($res["results"] as $proxy) {
                $result[] = $this->formatProxy($proxy);
            }
        }
        return $result;
    }

    public function formatProxy($proxy): array
    {
        return [
            "external_id" => $proxy["id"],
            "username" => $proxy["username"],
            "password" => $proxy["password"],
            "ip" => $proxy["proxy_address"],
            "port" => $proxy["port"],
            "country" => $proxy["country_code"],
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function getCountries(bool $full_names = false): array
    {
        $countries = $this->api->getCountries();

        $countries_information = [];

        foreach ($countries as $key => $count) {
            $countries_information[$key] = [
                'count' => $count,
                'full_name' => config('proxy.country_codes.' . $key)
            ];
        }

        return $countries_information;
    }
}
