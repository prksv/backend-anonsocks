<?php

namespace App\Http\Controllers\Api\v1_admin;

use App\Connectors\Webshare;
use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\WebshareAccountType;
use App\Facades\ProxyManager;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

class WebshareController extends ApiController
{
    /**
     * @throws GuzzleException
     */
    public function plan(Request $request)
    {
        $webshare = new Webshare(WebshareAccountType::from($request->plan_name));
        return $this->okResponse('', $webshare->getPlan());
    }

    public function types()
    {
        $types = array_column(WebshareAccountType::cases(), 'value');

        return $this->okResponse('', $types);
    }
}
