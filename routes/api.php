<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::get('refresh', 'refresh');
        Route::get('user/username/{id}', 'getUserName');
        Route::post('logout', 'logout');
        //Route::get('tetsuchun', 'tetsuchun');
        Route::get('user', 'user')->middleware('auth:sanctum');

        //        Route::controller(Web3LoginController::class)->prefix('web3')->group(function () {
        //            Route::view('/', 'web3');
        //            Route::get('/login', 'message')->name('web3.login');
        //            Route::post('/verify', 'verify')->name('web3.verify');
        //        });
        //
        //        Route::controller(TelegramController::class)->prefix('telegram')->group(function () {
        //            Route::post('/login', 'loginTelegram')->name('telegram.login');
        //        });

    });
});
