<?php

use App\Models\ThirdPartyApplication;
use Illuminate\Support\Facades\Route;

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
    $test = ThirdPartyApplication::create([
        "name" => "TelegramAdminBot",
    ]);
    dd($test->createToken("access"));
});

Route::get("/test", function () {
    return "yay";
})->middleware("signed-url");
