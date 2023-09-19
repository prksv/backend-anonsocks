<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \App\Proxy\ProxyManager
 * @see \App\Proxy\ProxyManager
 */
class ProxyManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return "proxy_manager";
    }
}
