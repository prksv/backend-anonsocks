<?php

namespace App\Services;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use App\Exceptions\CustomException;
use App\Facades\ProxyManager;
use App\Models\Category;
use App\Models\Order;
use App\Models\Proxy;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProxyService
{
    /**
     * @throws \Throwable
     */
    public function buy(
        User $user,
        Category $category,
        string $country_code,
        int $rental_days,
        int $count
    ) {
        $rentalTerm = $category
            ->rentalTerms()
            ->where("days", $rental_days)
            ->first();

        throw_if(!$rentalTerm, new CustomException("This rental period is not available"));

        $amount = $rentalTerm->price * $count;

        DB::beginTransaction();

        $proxies = ProxyManager::proxyType(ProxyType::get($category->proxy_type_id))->getProxies(
            $country_code,
            $count
        );

        $order = $user->orders()->create(compact("amount"));
        $order->proxies()->attach($proxies->pluck("id"), [
            "expires_at" => Carbon::now()->addMonths(4),
        ]);

        DB::commit();

        return $proxies;
    }
}
