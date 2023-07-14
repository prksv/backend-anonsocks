<?php

namespace App\Jobs\CoinbaseWebhooks;

use App\Enums\Deposit\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Shakurov\Coinbase\Models\CoinbaseWebhookCall;

class HandleConfirmedCharge implements ShouldQueue
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

        $amount = $data["pricing"]["local"]["amount"];
        $user_id = $data["metadata"]["user_id"];

        $user = User::findOrFail($user_id);

        Deposit::firstWhere("internal_id", $data["code"])->update([
            "status" => DepositStatus::COMPLETED,
        ]);

        $user->increment("balance", $amount);
    }
}
