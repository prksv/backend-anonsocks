<?php

use App\Http\Controllers\Api\v1\Proxy\ProxyPurchaseController;
use App\Http\Controllers\Api\v1\User\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix("user")->group(function () {
    Route::post("register", [UserController::class, "register"]);
    Route::post("login", [UserController::class, "login"]);
    Route::middleware("auth:sanctum")->group(function () {
        Route::post("/", [UserController::class, "index"]);
    });
});

Route::prefix("proxy")->group(function () {
    Route::post("{category}/buy", [ProxyPurchaseController::class, "index"])->middleware("auth:sanctum");
});

Route::get("/", function () {
    \App\Models\Proxy::create([
        "ip" => "127.0.0.1",
        "external_id" => Str::random(16),
        "port" => "1337",
        "username" => "dev_login",
        "password" => "dev_password",
        "country" => "US",
        "type" => \App\Enums\Proxy\ProxyType::IPV4_PREMIUM->value,
        "provider" => \App\Enums\Proxy\ProxyProvider::WEBSHARE,
    ]);
});
