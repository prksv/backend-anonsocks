<?php

namespace App\Jobs\CoinbaseWebhooks;

use App\Enums\Deposit\DepositStatus;
use App\Models\Deposit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shakurov\Coinbase\Models\CoinbaseWebhookCall;

class HandleFailedCharge implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public CoinbaseWebhookCall $webhookCall;
    private array|null $payload;

    public function __construct(CoinbaseWebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
        $this->payload = $this->webhookCall->payload;
    }

    public function handle()
    {
        $data = $this->payload["event"]["data"];

        Deposit::firstWhere("internal_id", $data["code"])->update([
            "status" => DepositStatus::FAILED,
        ]);
    }
}
