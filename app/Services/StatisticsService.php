<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\Order;
use App\Models\OrderProxy;
use App\Models\Proxy;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use QuickChart;

class StatisticsService
{
    private QuickChart $chart;

    private static array $configs = [
        'pie' => [
            "options" => [
                "plugins" => [
                    "datalabels" => [
                        "color" => 'white',
                        "display" => true,
                        "font" => [
                            "size" => 20,
                            "weight" => 'bold'
                        ]
                    ]
                ]
            ]
        ]
    ];

    public function __construct()
    {
        $this->chart = new QuickChart([
            'width' => 600,
            'height' => 350
        ]);


    }

    private function getDates(int $sub_days = 30): array
    {
        $dates = [];

        $period = CarbonPeriod::create(
            Carbon::now()->subDays($sub_days),
            ($sub_days !== 1) ? '1 day' : '1 hour',
            Carbon::now()
        );

        foreach ($period as $date) {
            $dates[$date->format(($sub_days !== 1) ? 'm-d' : 'H:00')] = 0;
        }

        return $dates;
    }

    public function getProxyPopularGraph(int $sub_days = 30): QuickChart
    {
        $orders = OrderProxy::where('created_at', '>=', Carbon::now()
            ->subDays($sub_days))
            ->get();

        $stats = [];

        $orders->each(function ($item) use ($sub_days, &$stats) {
            $country = $item->proxy->country;
            $stats[$country] = ($stats[$country] ?? 0) + 1;
        });

        $this->chart->setConfig(json_encode([
            "type" => "pie",
            "data" => [
                'labels' => array_keys($stats),
                "datasets" => [
                    [
                        "label" => "Proxies",
                        "data" => array_values($stats)
                    ],
                ],
            ],
            ...self::$configs['pie']
        ]));

        return $this->chart;
    }

    public function getOrdersGraph(int $sub_days = 30): QuickChart
    {
        $dates = $this->getDates($sub_days);

        $orders_amount = $orders_count = $dates;

        $orders = Order::where('created_at', '>=', Carbon::now()->subDays($sub_days))->get();

        $orders->each(function ($order) use ($sub_days, &$orders_amount, &$orders_count) {
            $date = $order->created_at->format(($sub_days !== 1) ? 'm-d' : 'H:00');
            $orders_count[$date] += 1;
            foreach ($order->proxies as $proxy) {
                $orders_amount[$date] = ($orders_amount[$date] ?? 0) + $proxy->pivot->rentalPeriods->sum('amount');
            }
        });

        $this->chart->setConfig(json_encode([
            "type" => "line",
            "data" => [
                'labels' => array_keys($dates),
                "datasets" => [
                    [
                        "label" => "Orders",
                        "data" => array_values($orders_count)
                    ],
                    [
                        "label" => "Orders amount",
                        "data" => array_values($orders_amount)
                    ]
                ]
            ],
        ]));

        return $this->chart;
    }

    public function getDepositsGraph(int $sub_days = 30): QuickChart
    {
        $dates = $this->getDates($sub_days);

        $deposits_amount = $deposits_count = $dates;

        $deposits = Deposit::where('created_at', '>=', Carbon::now()->subDays($sub_days))->get();

        $deposits->each(function ($deposit) use (&$deposits_amount, &$deposits_count, $sub_days) {
            $date = $deposit->created_at->format(($sub_days !== 1) ? 'm-d' : 'H:00');
            $deposits_count[$date] += 1;
            $deposits_amount[$date] = ($deposits_amount[$date] ?? 0) + $deposit->amount;
        });

        $this->chart->setConfig(json_encode([
            "type" => "line",
            "data" => [
                'labels' => array_keys($dates),
                "datasets" => [
                    [
                        "label" => "Deposits",
                        "data" => array_values($deposits_count)
                    ],
                    [
                        "label" => "Deposits amount",
                        "data" => array_values($deposits_amount)
                    ]
                ]
            ],
        ]));

        return $this->chart;
    }

    public function getUsersStats(int $sub_days = 30): array
    {
        $users = User::where('created_at', '>=', Carbon::now()->subDays($sub_days))->get();

        return [
            'balances_amount' => $users->sum('balance'),
            'users_count' => $users->count(),
        ];
    }
}
