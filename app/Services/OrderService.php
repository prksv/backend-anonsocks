<?php

namespace App\Services;

use App\Enums\Order\OrderStatus;
use App\Enums\Proxy\ProxyType;
use App\Exceptions\CustomException;
use App\Jobs\PurchaseProxy;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use QuickChart;
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
        User   $user,
        string $category,
        string $country_code,
        int    $rental_days,
        int    $count
    ): Order
    {
        DB::beginTransaction();

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
            "category_id" => $category->id,
            "proxy_count" => $count,
            "country" => $country_code,
        ]);

        PurchaseProxy::dispatch(
            $order,
            $category->proxy_type,
            $country_code,
            $count
        );

        DB::commit();

        return $order;
    }

    /**
     * @throws Throwable
     */
    public function refund(Order $order, array|null $proxy_ids): Order
    {
        Log::debug(gettype($proxy_ids) . json_encode($proxy_ids));
        DB::beginTransaction();

        throw_if($order->isRefunded(), new CustomException("Order is already refunded"));

        if (!$proxy_ids) {
            $order->update(['status' => OrderStatus::REFUNDED]);
        }

        $proxies = $order->proxies->when($proxy_ids, function ($query) use ($proxy_ids) {
            $query->whereIn('id', $proxy_ids);
        });

        foreach ($proxies as $proxy) {
            if ($proxy->expires_at >= Carbon::now()) {
                $rental_periods = $proxy->pivot->rentalPeriods()->whereNotExpired()->get();

                foreach ($rental_periods as $rental_period) {
                    $order->user()->increment('balance', $rental_period->amount);
                    $rental_period->update(['expires_at' => Carbon::now()]);
                }
            }
        };

        DB::commit();
        return $order;
    }
}
