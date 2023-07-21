<?php

namespace App\Http\Controllers\Api\v1\Deposit;

use App\Http\Controllers\ApiController;
use App\Http\Resources\DepositResource;
use App\Services\DepositService;
use Illuminate\Http\Request;

/**
 * @group Deposits
 */
class DepositController extends ApiController
{
    private DepositService $depositService;

    public function __construct()
    {
        $this->depositService = new DepositService();
    }

    /**
     * Список депозитов
     *
     * Получить все депозиты юзера
     *
     * @authenticated
     */
    public function index(Request $request)
    {
        $deposits = $this->depositService->getDeposits($request->user());

        return $this->okResponse("Deposits list", DepositResource::collection($deposits));
    }

    /**
     * Создать депозит
     *
     * Создает заявку на депозит и возращает URL coinbase оплаты
     *
     * @authenticated
     */
    public function create(Request $request)
    {
        $request->validate([
            "amount" => "required|integer|min:1",
        ]);

        $deposit = $this->depositService->create($request->user(), $request->amount);

        return $this->okResponse("Deposit request created.", $deposit);
    }
}
