<?php

use App\Models\ThirdPartyApplication;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
    return \App\Models\User::find(5455283697)->createToken('access')->plainTextToken;
});

Route::get("/test", function () {
    return "yay";
})->middleware("signed-url");
