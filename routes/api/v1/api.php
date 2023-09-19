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


Route::prefix("category")->group(function () {
    Route::get("/", [CategoryController::class, "index"]);
    Route::prefix("{category_name}")->group(function () {
        Route::get("countries", [CategoryController::class, "countries"]);
    });
});

Route::prefix("proxy")->group(function () {
    Route::get("list/{user_id}.txt", [ProxyController::class, "download"])
        ->middleware("signed-url")
        ->name("download-proxy");
});

Route::middleware("auth:sanctum")->group(function () {
    Route::get("thirdparty", function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::prefix("order")->group(function () {
        Route::get("/", [OrderController::class, "index"]);
        Route::prefix("{order_id}")->group(function () {
            Route::prefix("proxy")->group(function () {
                Route::prefix("{proxy_id}")->group(function () {
                    Route::get("extend", [ProxyController::class, "extend"]);
                });
            });
        });
    });

    Route::prefix("proxy")->group(function () {
        Route::get("/", [ProxyController::class, "index"]);
        Route::post("{category}/buy", [OrderController::class, "purchase"]);
        Route::post("export", [ProxyController::class, "export"]);
        Route::prefix("{proxy_id}")->group(function () {
        });
    });

    Route::prefix("deposit")->group(function () {
        Route::get("/", [DepositController::class, "index"]);
        Route::get("/{internal_id}", [DepositController::class, "view"]);
        Route::post("create", [DepositController::class, "create"]);
    });

    Route::prefix("user")->group(function () {
        Route::get("/", [UserController::class, "index"]);
        Route::get("refreshToken", [UserController::class, "refreshToken"]);
    });
});
