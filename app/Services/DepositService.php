<?php

namespace App\Services;

use App\Enums\Deposit\DepositStatus;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CoinbaseChargeResource;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Shakurov\Coinbase\Facades\Coinbase;

class DepositService
{
    /**
     * Создать счет Coinbase. Временное решение, нужно переписать под драйвер
     *
     * @param User $user
     * @param int $amount
     * @return array
     */

    private function createCoinbaseCharge(User $user, int $amount): array
    {
        $charge = Coinbase::createCharge([
            "name" => "Deposit funds to user #{$user->id}.",
            "description" => "Replenishment of funds on the " . config("app.name"),
            "local_price" => [
                "amount" => $amount,
                "currency" => "USD",
            ],
            "pricing_type" => "fixed_price",
            "metadata" => [
                "user_id" => $user->id,
            ],
        ]);

        return [
            "url" => $charge["data"]["hosted_url"],
            "amount" => $charge["data"]["pricing"]["local"]["amount"],
            "currency" => $charge["data"]["pricing"]["local"]["currency"],
            "internal_id" => $charge["data"]["code"],
        ];
    }

    public function create(User $user, int $amount)
    {
        $deposit = $this->createCoinbaseCharge($user, $amount);

        $user->deposits()->create([
            "internal_id" => $deposit["internal_id"],
            "amount" => $amount,
        ]);

        return $deposit;
    }

    public function getDeposits(User $user)
    {
        return $user->deposits;
    }
}
