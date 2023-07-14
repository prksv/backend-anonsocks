<?php

namespace App\Enums\Proxy;

use Henzeb\Enumhancer\Concerns\Enhancers;

enum ProxyType: int
{
    use Enhancers;

    case IPV4_PREMIUM = 1;
    case IPV4_SHARED = 2;
    case IPV4_SHARED_FREE = 3;

    public function getLabel(): string
    {
        return match ($this) {
            ProxyType::IPV4_SHARED => "IPv4 Proxy",
            ProxyType::IPV4_PREMIUM => "IPv6 GOLD",
            ProxyType::IPV4_SHARED_FREE => "IPv4 Free",
        };
    }
}
