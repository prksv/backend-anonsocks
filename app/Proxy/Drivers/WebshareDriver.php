<?php

namespace App\Proxy\Drivers;

use App\ApiWrappers\Webshare;
use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyStatus;
use App\Enums\Proxy\ProxyType;
use App\Enums\Proxy\WebshareAccountType;
use App\Models\Proxy;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
    public function getAllProxies(): array
    {
        $result = [];
        $response = $this->api->getProxiesList(null, 10000000);
        foreach ($response as $proxy) {
            $result[] = $this->formatProxy($proxy);
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
        foreach ($response as $replace) {
            $proxy = Proxy::where("ip", $replace["proxy"])->first();
            $replacedProxy = $proxy->replicate();

            $replacedProxy->ip = $replace["replaced_with"];
            $replacedProxy->port = $replace["replaced_with_port"];
            $replacedProxy->country = $replace["replaced_with_country_code"];

            $proxy->update(["status" => ProxyStatus::REPLACED]);

            $replacedProxy->save();

            $proxies = $proxies->merge($replacedProxy);
        }
        return $proxies;
    }

    /**
     * @throws GuzzleException
     */
    public function getProxies(string $country_code, int $count)
    {
        $result = collect();
        $to_replace = collect();

        $result = $result->merge($this->getSuitableFromMainPool($country_code, $count));

        $toPay = $this->api->getPrice([
            $country_code => $count - $result->count(),
        ]);

        if ($result->count() < $count) {
            $to_replace = $to_replace->merge($this->getFromPriorityPool($country_code, $count - $result->count()));
        }

        if ($result->count() < $count) {
            if ($toPay["paid_today"] > 2 || $this->api->proxy_replacements_available < $count - $result->count()) {
                Log::debug("bought");
                $result = $result->merge($this->buy([$country_code => $count - $result->count()]));
            } else {
                $to_replace = $to_replace->merge($this->getFromMainPool($country_code, $count - $result->count()));
            }
        }

        if ($to_replace->isNotEmpty()) {
            Log::debug("replaced");
            $result = $result->merge($this->makeReplace($to_replace->pluck("ip"), [$country_code => $count]));
        }

        return $result;
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

    private function getFromMainPool(string $country_code, int $count)
    {
        return Proxy::whereProviderAndType(self::$provider_id, $this->proxyType)
            ->whereNot("country", $country_code)
            ->limit($count)
            ->get();
    }

    /**
     * @throws GuzzleException
     */
    private function buy(array $countries): Collection
    {
        $minimumCount = floor(1 / $this->api->getPerProxyPrice());
        $countOfNeededProxies = array_sum($countries);

        if ($countOfNeededProxies < $minimumCount) {
            $countries["ZZ"] = $minimumCount - $countOfNeededProxies;
        }

        $exists_results = Proxy::whereIn("country", array_keys($countries))->get();

        $this->api->buyProxies($countries);
        $this->sync();

        $results_after_sync = Proxy::whereIn("country", array_keys($countries))->get();

        return $results_after_sync->diff($exists_results);
    }
}
