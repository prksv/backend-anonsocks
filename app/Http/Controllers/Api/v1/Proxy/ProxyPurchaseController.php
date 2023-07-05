<?php

namespace App\Http\Controllers\Api\v1\Proxy;

use App\Enums\Proxy\ProxyType;
use App\Http\Controllers\ApiController;
use App\Http\Requests\ProxyPurchaseRequest;
use App\Models\Category;
use App\Services\ProxyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LVR\CountryCode\Two;

class ProxyPurchaseController extends ApiController
{
    private ProxyService $proxyService;

    public function __construct()
    {
        $this->proxyService = new ProxyService();
    }

    /**
     * @throws \Throwable
     */
    public function index(ProxyPurchaseRequest $request)
    {
        $category = Category::where("name", $request->category)->first();

        if (!$category?->available) {
            return $this->clientErrorResponse("Category is not available");
        }

        $res = $this->proxyService->buy(
            $request->user(),
            $category,
            $request->country_code,
            $request->rental_days,
            $request->count
        );
        Log::debug(json_encode($res));
        return $this->okResponse("test", $res);
    }
}
