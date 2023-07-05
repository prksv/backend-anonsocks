<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class ProxyManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return "proxy_manager";
    }
}
