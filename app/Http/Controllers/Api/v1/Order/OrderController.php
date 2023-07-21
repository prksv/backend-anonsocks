<?php

namespace App\Http\Controllers\Api\v1\Order;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ProxyPurchaseRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Throwable;

/**
 * @group Orders
 *
 * @authenticated
 */
class OrderController extends ApiController
{
    private OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    /**
     * Список заказов
     *
     * Получить все заказы юзера
     */
    public function index(Request $request)
    {
        $request->validate([
            "full" => "boolean",
        ]);

        $orders = $this->orderService->getOrders($request->user());

        return $this->okResponse("Your orders", OrderResource::collection($orders));
    }

    /**
     * Купить прокси
     *
     * @throws Throwable
     *
     * @urlParam category Example: ipv6_64
     * @bodyParam country_code string Country code Example: UA
     */
    public function purchase(ProxyPurchaseRequest $request)
    {
        $order = $this->orderService->create(
            $request->user(),
            $request->category,
            $request->country_code,
            $request->rental_days,
            $request->count
        );
        return $this->okResponse("Order created", new OrderResource($order));
    }
}
