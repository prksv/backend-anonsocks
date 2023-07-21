<?php

namespace App\Proxy;

use App\Proxy\Drivers\NullDriver;
use App\Proxy\Drivers\WebshareDriver;
use Illuminate\Support\Manager;

class ProxyManager extends Manager
{
    public function createWebshareDriver()
    {
        return new WebshareDriver();
    }

    public function getDefaultDriver(): string
    {
        return "webshare";
    }
}
