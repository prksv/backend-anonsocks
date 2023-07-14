<?php

use App\Connectors\Webshare;
use App\Enums\Proxy\ProxyType;
use App\Enums\Proxy\WebshareAccountType;
use App\Facades\ProxyManager;
use App\Models\Proxy;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get("/", function () {
    return \Spatie\UrlSigner\Laravel\Facades\UrlSigner::sign(url("test"), now()->addMinute());
});

Route::get("/test", function () {
    return "yay";
})->middleware("signed-url");
