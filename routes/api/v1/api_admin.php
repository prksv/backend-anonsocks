<?php

use App\Http\Controllers\Api\v1_admin\Category\CategoryController;
use App\Http\Controllers\Api\v1_admin\Category\RentalTermController;
use App\Http\Controllers\Api\v1_admin\Order\OrderController;
use App\Http\Controllers\Api\v1_admin\Proxy\ProxyController;
use App\Http\Controllers\Api\v1_admin\Statistics\StatisticsController;
use App\Http\Controllers\Api\v1_admin\User\UserController;
use App\Http\Controllers\Api\v1_admin\WebshareController;
use \Illuminate\Support\Facades\Route;

Route::prefix('proxy')->group(function () {
    Route::post('export', [ProxyController::class, 'export']);
    Route::get('/', [ProxyController::class, 'index']);
    Route::get('types', [ProxyController::class, 'getTypes']);
    Route::get('providers', [ProxyController::class, 'getProviders']);
    Route::get('sync', [ProxyController::class, 'sync']);
    Route::prefix('{proxy_id}')->group(function () {
        Route::get('/', [ProxyController::class, 'view']);
        Route::get('replace', [ProxyController::class, 'replace']);
    });
});

Route::prefix('category')->group(function () {
    Route::post('create', [CategoryController::class, 'create']);
    Route::prefix('{category_name}')->group(function () {
        Route::get('/', [CategoryController::class, 'view']);
        Route::prefix('rental_term')->group(function () {
            Route::post('create', [RentalTermController::class, 'create']);
        });
    });
});

Route::prefix('statistics')->group(function () {
    Route::get('orders', [StatisticsController::class, 'orders']);
    Route::get('proxy_popular', [StatisticsController::class, 'proxyPopular']);
    Route::get('deposits', [StatisticsController::class, 'deposits']);
    Route::get('users', [StatisticsController::class, 'users']);
});

Route::prefix('user')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::prefix('{user_id}')->group(function () {
        Route::get('/', [UserController::class, 'view']);
        Route::prefix('role/{role}')->group(function () {
            Route::get('take', [UserController::class, 'takeRole']);
            Route::get('give', [UserController::class, 'giveRole']);
        });
        Route::post('giveMoney', [UserController::class, 'giveMoney']);
    });
    Route::prefix('telegram/{telegram_id}')->group(function () {
        Route::get('find', [UserController::class, 'findByTelegram']);
        Route::post('linkByToken', [UserController::class, 'linkTelegramByToken']);
    });});

Route::prefix('order')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::prefix('{order_id}')->group(function () {
        Route::get('/', [OrderController::class, 'view']);
        Route::post('refund', [OrderController::class, 'refund']);
    });
});

Route::prefix('webshare')->group(function () {
    Route::get('/types', [WebshareController::class, 'types']);
    Route::get('{plan_name}/plan', [WebshareController::class, 'plan']);
});

Route::get('/', [UserController::class, 'me'])->middleware("auth:sanctum");

