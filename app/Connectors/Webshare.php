<?php

namespace App\Connectors;

use App\Enums\Proxy\WebshareAccountType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class Webshare
{
    const BASE_URL = "https://proxy.webshare.io/api/v2/";
    const BANWIDTH_LIMIT = 250;
    private string $api_key;
    private Client $client;
    /**
     * @var $plan array{
     *       id: number,
     *       bandwidth_limit: number,
     *       monthly_price: number,
     *       yearly_price: number,
     *       proxy_type: string,
     *       proxy_subtype: string,
     *       proxy_count: number,
     *       proxy_countries: array,
     *        required_site_checks: string[],
     *       on_demand_refreshes_total: number,
     *       on_demand_refreshes_used: number,
     *       on_demand_refreshes_available: number,
     *       automatic_refresh_frequency: number,
     *       automatic_refresh_last_at: mixed,
     *       automatic_refresh_next_at: mixed,
     *       proxy_replacements_total: number,
     *       proxy_replacements_used: number,
     *       proxy_replacements_available: number,
     *       subusers_total: number,
     *       subusers_used: number,
     *       subusers_available: number,
     *       is_unlimited_ip_authorizations: boolean,
     *       is_high_concurrency: boolean,
     *       is_high_priority_network: boolean,
     *       created_at: string,
     *       updated_at: string
     * }
     */
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
    public function __get(string $variable)
    {
        $func = "get" . ucfirst($variable);
        if (method_exists($this, $func)) {
            return $this->$func();
        }
        return $this->$variable;
    }

    /**
     * @throws GuzzleException
     */
    public function getProxiesList(string $country = null, int $count = null, int $page = 1): array
    {
        $params = ["mode" => "direct", "page" => $page];

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
            return json_decode($response->getBody(), true);
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
        Log::debug(json_encode(json_decode($response->getBody(), true)));
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

    /**
     * @throws GuzzleException
     */
    public function getPerProxyPrice()
    {
        return $this->getPlan()["monthly_price"] / $this->getPlan()["proxy_count"];
    }

    /**
     * @throws GuzzleException
     */
    public function getPlan(): array
    {
        if (!$this->plan) {
            $response = $this->client->get("subscription/plan/", [
                "query" => ["ordering" => "-created_at"],
            ]);
            $this->plan = json_decode($response->getBody(), true)["results"][0];
        }

        return $this->plan;
    }

    /**
     * @param int[] $countries
     * @throws GuzzleException
     */
    public function getPrice(array $countries)
    {
        $proxy_countries = $this->getPlan()["proxy_countries"];

        foreach ($countries as $key => $value) {
            $proxy_countries[$key] = ($proxy_countries[$key] ?? 0) + $value;
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
                    "proxy_replacements_total" =>
                        $this->getPlan()["proxy_replacements_available"] + array_sum($countries),
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
    public function buyProxies(array $countries, int $payment_method, string $recaptcha)
    {
        $proxy_countries = $this->getPlan()["proxy_countries"];

        foreach ($countries as $key => $value) {
            $proxy_countries[$key] = ($proxy_countries[$key] ?? 0) + $value;
        }

        $response = $this->client->post("subscription/checkout/purchase/", [
            "json" => [
                "proxy_type" => $this->proxyType,
                "proxy_subtype" => $this->proxySubType,
                "proxy_countries" => $proxy_countries,
                "bandwidth_limit" => self::BANWIDTH_LIMIT,
                "on_demand_refreshes_total" => 0,
                "automatic_refresh_frequency" => 0,
                "proxy_replacements_total" =>
                    $this->getPlan()["proxy_replacements_available"] + array_sum($countries),
                "subusers_total" => 3,
                "is_unlimited_ip_authorizations" => false,
                "is_high_concurrency" => false,
                "is_high_priority_network" => false,
                "term" => "monthly",
                "payment_method" => $payment_method,
                "recaptcha" => $recaptcha,
            ],
        ]);
        Log::debug(json_decode($response->getBody(), true));
        return json_decode($response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function getSubscription(): array
    {
        $response = $this->client->get("subscription/");
        return json_decode($response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function getPendingPayments(): array
    {
        $response = $this->client->get("payment/pending/");
        return json_decode($response->getBody(), true);
    }

    public function pendingPayment(int $id)
    {
        $response = $this->client->get("payment/pending/${id}/");
        return json_decode($response->getBody(), true);
    }

    public function processPayment(int $id, array $data)
    {
        $response = $this->client->post("payment/pending/${id}/process/", [
            "json" => $data,
        ]);
        return json_decode($response->getBody(), true);
    }
}
