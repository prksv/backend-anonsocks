<?php

namespace App\Enums\Proxy;

use Henzeb\Enumhancer\Concerns\Enhancers;

enum ProxyType: int
{
    use Enhancers;

    case IPV4_PREMIUM = 1;
    case IPV4_SHARED = 2;
    case IPV4_SHARED_FREE = 3;
}
