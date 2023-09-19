<?php

namespace App\Http\Controllers\Api\v1_admin\Order;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    private OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    public function index(Request $request)
    {
        $orders = Order::paginate($request->per_page);

        return $this->resourceCollectionResponse(OrderResource::collection($orders), '', false);
    }

    public function view(Request $request)
    {
        $order = Order::findOrFail($request->order_id);

        return $this->okResponse('', new OrderResource($order));
    }

    /**
     * @throws \Throwable
     */
    public function refund(Request $request)
    {
        $request->validate([
            'proxy_ids' => 'array'
        ]);

        $order = Order::findOrFail($request->order_id);
        $replaced_order = $this->orderService->refund($order, $request->proxy_ids);

        return $this->okResponse('', new OrderResource($replaced_order));
    }
}
