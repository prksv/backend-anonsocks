<?php

namespace App\Enums\Proxy;

use Henzeb\Enumhancer\Concerns\Enhancers;

enum ProxyProvider: int
{
    use Enhancers;

    case WEBSHARE = 0;
    case XYECOC = 1337;
}
