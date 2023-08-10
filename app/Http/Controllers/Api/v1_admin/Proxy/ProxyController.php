<?php

namespace App\Http\Controllers\Api\v1_admin\Proxy;

use App\Facades\ProxyManager;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProxyResource;
use App\Models\Proxy;
use App\Services\ProxyService;
use Illuminate\Http\Request;

class ProxyController extends ApiController
{
    private ProxyService $proxyService;

    public function __construct()
    {
        $this->proxyService = new ProxyService();
    }

    public function index(Request $request)
    {
        $proxies = Proxy::paginate($request->per_page);
        return $this->resourceCollectionResponse(ProxyResource::collection($proxies), 'proxy list', false);
    }

    public function sync(Request $request)
    {
        $sync_result = ProxyManager::sync();
        return $this->okResponse('Synced', $sync_result);
    }
}
