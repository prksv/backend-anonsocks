<?php

namespace App\Jobs;

use App\Enums\Order\OrderStatus;
use App\Enums\Proxy\ProxyType;
use App\Facades\ProxyManager;
use App\Models\Order;
use App\Models\ProxyRentalPeriod;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseProxy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Order $order;
    private ProxyType $proxyType;
    private string $country_code;
    private int $count;
    private OrderService $orderService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Order     $order,
        ProxyType $proxyType,
        string    $country_code,
        int       $count
    )
    {
        Log::debug($count);
        $this->order = $order;
        $this->orderService = new OrderService();
        $this->proxyType = $proxyType;
        $this->country_code = $country_code;
        $this->count = $count;
    }

    /**
     * Execute the job.
     * @throws \Throwable
     */
    public function handle(): void
    {
        DB::beginTransaction();

        try {
            $proxies = ProxyManager::proxyType($this->proxyType)->getProxies(
                $this->country_code,
                $this->count
            );

            $this->order->update([
                "status" => OrderStatus::DONE,
            ]);

            $this->order->proxies()->attach($proxies->pluck("id")->all());

            $rentalTerm = $this->order->rentalTerm;

            foreach ($this->order->proxies as $proxy) {
                ProxyRentalPeriod::create([
                    'order_proxy_id' => $proxy->pivot->id,
                    'rental_term_id' => $rentalTerm->id,
                    'amount' => $rentalTerm->price,
                    'expires_at' => Carbon::now()->addDays($rentalTerm->days)
                ]);
            };

            DB::commit();
        } catch (\Exception $e) {
            Log::debug($e);
            DB::rollback();
            $this->fail($e);
        }
    }
}
