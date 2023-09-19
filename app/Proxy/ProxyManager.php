<?php

namespace App\Proxy;

use App\Proxy\Drivers\WebshareDriver;
use App\Proxy\Drivers\XyecocDriver;
use Illuminate\Support\Manager;

class ProxyManager extends Manager
{
    /**
     * @return \App\Proxy\Drivers\WebshareDriver
     */
    public function createWebshareDriver()
    {
        return new WebshareDriver();
    }

    public function createXyecocDriver()
    {
        return new XyecocDriver();
    }

    public function getDefaultDriver(): string
    {
        return "webshare";
    }
}
