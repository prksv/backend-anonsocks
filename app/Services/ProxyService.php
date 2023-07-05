<?php

namespace App\Services;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use App\Exceptions\CustomException;
use App\Facades\ProxyManager;
use App\Models\Category;
use App\Models\Proxy;
use App\Models\User;
use Illuminate\Support\Collection;

class ProxyService
{
    /**
     * @throws \Throwable
     */
    public function buy(User $user, Category $category, string $country_code, int $rental_days, int $count)
    {
        $rentalTerm = $category
            ->rentalTerms()
            ->where("days", $rental_days)
            ->first();

        throw_if(!$rentalTerm, new CustomException("This rental period is not available"));

        $proxies = ProxyManager::proxyType(ProxyType::get($category->proxy_type_id))->getProxies($country_code, $count);
        return $proxies;
    }
}
