<?php

namespace App\Proxy\Drivers;

use App\ApiWrappers\Webshare;
use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyStatus;
use App\Enums\Proxy\ProxyType;
use App\Enums\Proxy\WebshareAccountType;
use App\Exceptions\CustomException;
use App\Models\Proxy;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
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
            ProxyType::IPV4_SHARED_FREE => throw new \Exception("To be implemented"),
        };

        $this->proxyType = $proxyType;
        $this->api = new Webshare($accountType);

        return $this;
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
     * Делает замену прокси на указаннные страны
     *
     * @param array $ip_addresses
     * @param int[] $replace_countries
     * @return Collection Список заменённых прокси
     * @throws GuzzleException
     */
    public function makeReplace(array $ip_addresses = [], array $replace_countries = []): Collection
    {
        Log::debug(json_encode($replace_countries));
        $requestId = $this->api->requestReplacement($ip_addresses, $replace_countries);
        $response = $this->api->getReplacedProxy($requestId);
        $proxies = collect();
        foreach ($response as $replaced) {
            $proxy = Proxy::where("ip", $replaced["proxy"])->first();
            $replacedProxy = $proxy->replicate();

            $replacedProxy->ip = $replaced["replaced_with"];
            $replacedProxy->port = $replaced["replaced_with_port"];
            $replacedProxy->country = $replaced["replaced_with_country_code"];

            $proxy->update(["status" => ProxyStatus::REPLACED]);

            $replacedProxy->save();

            $proxies = $proxies->merge($replacedProxy);
        }
        return $proxies;
    }

    /**
     * @throws GuzzleException|\Throwable
     */
    public function getProxies(string $country_code, int $count): Collection
    {
        $result = collect();
        $to_replace = collect();

        $suitable_proxy = $this->getSuitableFromMainPool($country_code, $count);
        $result = $result->merge($suitable_proxy);

        $remains = $count - $result->count() - $to_replace->count();

        if ($remains > 0) {
            $available_replaces = $this->api->plan["proxy_replacements_available"];

            $pricing = $this->api->getPrice([
                $country_code => $remains,
            ]);

            if ($pricing["paid_today"] > 2) {
                $result = $result->merge($this->buy([$country_code => $remains]));
            }
        }

        $remains = $count - $result->count() - $to_replace->count();

        if ($remains > 0) {
            $priority_proxy = $this->getFromPriorityPool(
                $country_code,
                min($available_replaces, $remains)
            );

            $available_replaces -= $priority_proxy->count();
            $to_replace = $to_replace->merge($priority_proxy);
        }

        $remains = $count - $result->count() - $to_replace->count();

        if ($remains > 0) {
            $main_proxy = $this->getFromMainPool($country_code, min($available_replaces, $remains));
            $to_replace = $to_replace->merge($main_proxy);
        }

        $remains = $count - $result->count() - $to_replace->count();

        if ($remains > 0) {
            $result = $result->merge($this->buy([$country_code => $remains]));
        }

        if ($to_replace->isNotEmpty()) {
            $to_replace_addresses = $to_replace->pluck("ip")->all();
            $result = $result->merge(
                $this->makeReplace($to_replace_addresses, [
                    $country_code => $count,
                ])
            );
        }

        return $result;
    }

    private function getFromMainPool(string $country_code, int $count)
    {
        return Proxy::whereProviderAndType(self::$provider_id, $this->proxyType)
            ->whereNot("country", $country_code)
            ->limit($count)
            ->get();
    }

    private function getSuitableFromMainPool(string $country_code, int $count)
    {
        return Proxy::whereProviderAndType(self::$provider_id, $this->proxyType)
            ->where("country", $country_code)
            ->limit($count)
            ->get();
    }

    private function getFromPriorityPool(string $country_code, int $count)
    {
        return Proxy::whereProviderAndType(self::$provider_id, $this->proxyType)
            ->fromPriorityPool()
            ->whereNot("country", $country_code)
            ->limit($count)
            ->get();
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
     * @throws GuzzleException
     * @throws \Throwable
     */
    private function buy(array $countries): Collection
    {
        $minimumCount = floor(1 / $this->api->getPerProxyPrice());
        $countOfNeededProxies = array_sum($countries);

        if ($countOfNeededProxies < $minimumCount) {
            $countries["ZZ"] = $minimumCount - $countOfNeededProxies;
        }

        $paymentData = $this->api->buyProxies(
            $countries,
            $this->api->getSubscription()["payment_method"],
            $this->solveCaptcha()
        );

        if ($paymentData["payment_required"]) {
            $this->confirmPayment(
                $paymentData["stripe_payment_intent"],
                $paymentData["stripe_payment_method"],
                $paymentData["stripe_client_secret"]
            );
            $pending_payment = $this->api->pendingPayment($paymentData["pending_payment"]);
            Log::debug(json_encode($pending_payment, true));
            $final = $this->api->processPayment($paymentData["pending_payment"], $pending_payment);
            Log::debug(json_encode($final, true));
        }

        $exists_results = Proxy::whereIn("country", array_keys($countries))->get();
        sleep(2);
        $this->sync();

        $results_after_sync = Proxy::whereIn("country", array_keys($countries))->get();

        return $results_after_sync->diff($exists_results);
    }
}
