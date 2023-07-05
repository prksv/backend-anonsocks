<?php

namespace App\ApiWrappers;

use App\Enums\Proxy\WebshareAccountType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Webshare
{
    const BASE_URL = "https://proxy.webshare.io/api/v2/";
    const BANWIDTH_LIMIT = 250;
    private string $api_key;
    private Client $client;
    private array $plan = [];
    private WebshareAccountType $accountType;
    private string $proxyType;
    private string $proxySubType;

    public function __construct(WebshareAccountType $accountType)
    {
        $this->api_key = config("proxy.services.webshare." . $accountType->value . ".api_key");
        $this->accountType = $accountType;

        $this->client = new Client([
            "base_uri" => self::BASE_URL,
            "headers" => [
                "Authorization" => "Token " . $this->api_key,
            ],
        ]);

        $this->proxyType = match ($accountType) {
            WebshareAccountType::PREMIUM => "dedicated",
            WebshareAccountType::DEFAULT => "shared",
        };

        $this->proxySubType = match ($accountType) {
            WebshareAccountType::PREMIUM => "premium",
            WebshareAccountType::DEFAULT => "default",
        };
    }

    /**
     * @throws GuzzleException
     */
    public function __get(string $attribute)
    {
        if (empty($plan)) {
            $this->plan = $this->getPlan();
        }
        return $this->plan[$attribute];
    }

    /**
     * @throws GuzzleException
     */
    public function getPlan(): array
    {
        $response = $this->client->get("subscription/plan/");
        return json_decode($response->getBody(), true)["results"][0];
    }

    /**
     * @throws GuzzleException
     */
    public function getProxiesList(string $country = null, int $count = null): array
    {
        $params = ["mode" => "direct"];

        if ($country) {
            $params["country_code__in"] = $country;
        }
        if ($count) {
            $params["page_size"] = $count;
        }

        $response = $this->client->get("proxy/list/", [
            "query" => $params,
        ]);

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody(), true)["results"];
        }
        return [];
    }

    /**
     * Создаёт заявку на замену проксей
     *
     * @param array $ip_addresses
     * @param int[] $replace_countries
     * @return int Номер заявки на реплейс
     * @throws GuzzleException
     */
    public function requestReplacement(array $ip_addresses = [], array $replace_countries = []): int
    {
        $replace_with = [];
        foreach ($replace_countries as $countryCode => $count) {
            $replace_with[] = [
                "type" => "country",
                "country_code" => $countryCode,
                "count" => $count,
            ];
        }

        $response = $this->client->post("proxy/replace/", [
            "json" => [
                "dry_run" => "false",
                "to_replace" => [
                    "type" => "ip_address",
                    "ip_addresses" => $ip_addresses,
                ],
                "replace_with" => $replace_with,
            ],
        ]);
        return json_decode($response->getBody(), true)["id"];
    }

    /**
     * Получает список заменённых прокси по номеру заявки реплейсмента
     *
     * @param int $replacementId
     * @return array
     * @throws GuzzleException
     */
    public function getReplacedProxy(int $replacementId): array
    {
        $response = $this->client->get("proxy/list/replaced/", [
            "query" => [
                "proxy_list_replacement" => $replacementId,
            ],
        ]);
        return json_decode($response->getBody(), true)["results"];
    }

    public function getPerProxyPrice()
    {
        return $this->monthly_price / $this->proxy_count;
    }

    /**
     * @param int[] $countries
     * @throws GuzzleException
     */
    public function getPrice(array $countries)
    {
        $proxy_countries = $this->proxy_countries;

        foreach ($countries as $key => $value) {
            $proxy_countries[$key] += $value;
        }

        $response = $this->client->get("subscription/pricing/", [
            "query" => [
                "query" => json_encode([
                    "proxy_type" => $this->proxyType,
                    "proxy_subtype" => $this->proxySubType,
                    "proxy_countries" => $proxy_countries,
                    "bandwidth_limit" => self::BANWIDTH_LIMIT,
                    "on_demand_refreshes_total" => 0,
                    "automatic_refresh_frequency" => 0,
                    "proxy_replacements_total" => $this->proxy_replacements_available + array_sum($countries),
                    "subusers_total" => 3,
                    "term" => "monthly",
                    "is_unlimited_ip_authorizations" => false,
                    "is_high_concurrency" => false,
                    "is_high_priority_network" => false,
                ]),
            ],
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function buyProxies(array $countries)
    {
        $proxy_countries = $this->proxy_countries;

        foreach ($countries as $key => $value) {
            $proxy_countries[$key] += $value;
        }

        $response = $this->client->post("subscription/checkout/purchase/", [
            "json" => [
                "proxy_type" => $this->proxyType,
                "proxy_subtype" => $this->proxySubType,
                "proxy_countries" => $proxy_countries,
                "bandwidth_limit" => self::BANWIDTH_LIMIT,
                "on_demand_refreshes_total" => 0,
                "automatic_refresh_frequency" => 0,
                "proxy_replacements_total" => $this->proxy_replacements_available + array_sum($countries),
                "subusers_total" => 3,
                "is_unlimited_ip_authorizations" => false,
                "is_high_concurrency" => false,
                "is_high_priority_network" => false,
                "term" => "monthly",
                "payment_method" => null,
                "recaptcha" => "eblanus12312",
            ],
        ]);
        return json_decode($response->getBody(), true);
    }
}
