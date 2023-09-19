<?php

namespace App\Http\Controllers\Api\v1_admin\Statistics;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use QuickChart;

class StatisticsController extends ApiController
{
    private StatisticsService $statisticsService;

    public function __construct()
    {
        $this->statisticsService = new StatisticsService();
    }

    /**
     * @throws \ErrorException
     */
    public function orders(Request $request)
    {
        $graph = $this->statisticsService->getOrdersGraph($request->sub_days ?? 30);
        return response($graph->toBinary(), 200, ['Content-Type' => 'image/jpeg']);
    }

    /**
     * @throws \ErrorException
     */
    public function deposits(Request $request)
    {
        $graph = $this->statisticsService->getDepositsGraph($request->sub_days ?? 30);
        return response($graph->toBinary(), 200, ['Content-Type' => 'image/jpeg']);
    }

    /**
     * @throws \ErrorException
     */
    public function proxyPopular(Request $request)
    {
        $graph = $this->statisticsService->getProxyPopularGraph($request->sub_days ?? 30);
        return response($graph->toBinary(), 200, ['Content-Type' => 'image/jpeg']);
    }

    public function users(Request $request)
    {
        $stats = $this->statisticsService->getUsersStats($request->sub_days ?? 30);
        return $this->okResponse('', $stats);
    }
}
