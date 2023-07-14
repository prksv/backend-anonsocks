<?php

namespace App\Services;

use App\Enums\Order\OrderStatus;
use App\Enums\Proxy\ProxyType;
use App\Exceptions\CustomException;
use App\Jobs\PurchaseProxy;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderService
{
    public function getOrders(User $user)
    {
        return $user->orders;
    }

    /**
     * @throws Throwable
     */
    public function create(
        User $user,
        string $category,
        string $country_code,
        int $rental_days,
        int $count
    ): Order {
        $category = Category::where("name", $category)->first();

        throw_if(!$category?->available, new CustomException("Category is not available"));

        $rentalTerm = $category
            ->rentalTerms()
            ->where("days", $rental_days)
            ->first();

        throw_if(!$rentalTerm, new CustomException("This rental period is not available"));

        $amount = $rentalTerm->price * $count;

        $user->decrementBalance($amount);

        $order = $user->orders()->create([
            "rental_term_id" => $rentalTerm->id,
            "amount" => $amount,
        ]);

        PurchaseProxy::dispatch(
            $order,
            ProxyType::get($category->proxy_type_id),
            $country_code,
            $count
        );

        return $order;
    }
}
