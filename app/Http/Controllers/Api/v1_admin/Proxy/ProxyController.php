<?php

namespace App\Http\Controllers\Api\v1_admin\Proxy;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use App\Enums\Proxy\WebshareAccountType;
use App\Facades\ProxyManager;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProxyResource;
use App\Models\Proxy;
use App\Models\User;
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

    public function view(Request $request)
    {
        $proxy = Proxy::findOrFail($request->proxy_id);
        return $this->okResponse('proxy', new ProxyResource($proxy));
    }

    public function sync(Request $request)
    {
        $sync_result = ProxyManager::sync();
        return $this->okResponse('Synced', $sync_result);
    }

    public function getTypes(Request $request)
    {
        return $this->okResponse('', array_column(ProxyType::cases(), 'name'));
    }

    public function getProviders(Request $request)
    {
        return $this->okResponse('', array_column(ProxyProvider::cases(), 'name'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $user = User::findOrFail($request->user_id);
        $download_url = $this->proxyService->export($user);
        return $this->okResponse('Download URL created. Link will available in 1 minute.', $download_url);
    }

    public function replace(Request $request)
    {
        $proxy = Proxy::findOrFail($request->proxy_id);

        $replaced_proxy = $this->proxyService->replace($proxy);

        return $this->okResponse('', new ProxyResource($replaced_proxy->first()));
    }
}
