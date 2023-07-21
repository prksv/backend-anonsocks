<?php

use App\Http\Controllers\Api\v1\Category\CategoryController;
use App\Http\Controllers\Api\v1\Deposit\DepositController;
use App\Http\Controllers\Api\v1\Order\OrderController;
use App\Http\Controllers\Api\v1\Proxy\ProxyController;
use App\Http\Controllers\Api\v1\User\UserController;
use Illuminate\Support\Facades\Route;

/*e
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post("register", [UserController::class, "register"]);
Route::post("login", [UserController::class, "login"]);

Route::prefix("proxy")->group(function () {
    Route::get("categories", [CategoryController::class, "index"]);
    Route::get("list/{user_id}.txt", [ProxyController::class, "download"])
        ->middleware("signed-url")
        ->name("download-proxy");
});

Route::middleware("auth:sanctum")->group(function () {
    Route::get("thirdparty", function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::prefix("orders")->group(function () {
        Route::get("/", [OrderController::class, "index"]);
    });

    Route::prefix("proxy")->group(function () {
        Route::get("/", [ProxyController::class, "index"]);
        Route::post("{category}/buy", [OrderController::class, "purchase"]);
        Route::post("export", [ProxyController::class, "export"]);
    });

    Route::prefix("deposit")->group(function () {
        Route::get("/", [DepositController::class, "index"]);
        Route::post("create", [DepositController::class, "create"]);
    });

    Route::prefix("user")->group(function () {
        Route::get("/", [UserController::class, "index"]);
    });
});
