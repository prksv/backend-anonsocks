<?php

namespace App\Http\Controllers\Api\v1\Proxy;

use App\Exceptions\CustomException;
use App\Http\Controllers\ApiController;
use App\Http\Resources\ProxyResource;
use App\Models\Order;
use App\Models\Proxy;
use App\Models\User;
use App\Services\ProxyService;
use Illuminate\Http\Request;

/**
 * @group Proxy
 */
class ProxyController extends ApiController
{
    private ProxyService $proxyService;

    public function __construct()
    {
        $this->proxyService = new ProxyService();
    }

    /**
     * Получить купленные прокси
     *
     * Получить все купленные прокси юзера
     *
     * @authenticated
     */
    public function index(Request $request)
    {
        $proxies = $this->proxyService->getProxies($request->user());

        return $this->okResponse("Your proxies", ProxyResource::collection($proxies));
    }

    /**
     * Экспортировать прокси
     *
     * Экспортировать все купленные и активные прокси юзера. Вернет URL на скачиванние
     *
     * @authenticated
     */
    public function export(Request $request)
    {
        $request->validate([
            "proxy_ids" => "array",
        ]);

        $download_url = $this->proxyService->export($request->user());

        return $this->okResponse(
            "Download URL created. Link will available in 1 minute.",
            $download_url
        );
    }

    public function download(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        return $this->proxyService->download($user);
    }

    /**
     * Продлить аренду
     *
     * Продлить срок аренды прокси
     *
     * @authenticated
     */
    public function extend(Request $request)
    {
        $request->validate([
            "rental_days" => "required|numeric|min:1",
        ]);

        $order = Order::findOrFail($request->order_id);

        throw_if($order->user_id !== $request->user()->id, new CustomException("Order is not yours."));

        $proxy = $this->proxyService->extend($request->user(), $order, $request->proxy_id, $request->rental_days);

        return $this->okResponse('Proxy rental period was extended', new ProxyResource($proxy));
    }
}
