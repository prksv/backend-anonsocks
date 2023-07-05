<?php

namespace App\Enums\Proxy;

enum ProxyStatus: int
{
    case ACTIVE = 0;
    case INACTIVE = 1;
    case REPLACED = 2;
}
