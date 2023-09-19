<?php

namespace App\Proxy\Drivers;

use App\Contracts\ProxyDriverInterface;
use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyStatus;
use App\Enums\Proxy\ProxyType;
use App\Models\Proxy;

abstract class Driver implements ProxyDriverInterface
{
    private static ProxyProvider $provider_id = ProxyProvider::WEBSHARE;
    protected bool $isPriorityPool = false;
    protected ProxyType $proxyType;

    public function fromPriorityPool()
    {
        $this->isPriorityPool = true;
    }

    public function sync(): void
    {
        $proxies = $this->getAllProxies();

        foreach ($proxies as $proxy) {
            Proxy::updateOrCreate(
                ["external_id" => $proxy["external_id"]],
                $proxy + [
                    "status" => ProxyStatus::ACTIVE,
                    "provider" => self::$provider_id,
                    "type" => $this->proxyType,
                ]
            );
        }
    }
}
