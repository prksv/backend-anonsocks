<?php

namespace App\Providers;

use App\Guards\CustomSanctumGuard;
use Illuminate\Auth\RequestGuard;
use Laravel\Sanctum\SanctumServiceProvider as SSP;

class SanctumServiceProvider extends SSP
{
    protected function createGuard($auth, $config): RequestGuard
    {
        return new RequestGuard(
            new CustomSanctumGuard($auth, config("sanctum.expiration"), $config["provider"]),
            request(),
            $auth->createUserProvider($config["provider"] ?? null)
        );
    }
}
