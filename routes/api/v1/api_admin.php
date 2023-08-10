<?php

use App\Http\Controllers\Api\v1_admin\Proxy\ProxyController;
use \Illuminate\Support\Facades\Route;

Route::prefix('proxy')->group(function () {
    Route::get('/', [ProxyController::class, 'index']);
   Route::get('sync', [ProxyController::class, 'sync']);
});
