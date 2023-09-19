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

class XyecocDriver extends Driver
{

    public function getProxies(string $country_code, int $count): Collection
    {
        return collect();
    }

    public function formatProxy($proxy): array
    {
        return [];
    }

    public function getCountries(): array
    {
        return [
                'UU' => [
                'count' => 1337,
                'full name' => 'Random'
            ]
        ];
    }
}
