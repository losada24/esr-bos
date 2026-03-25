<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\Mobile\MobileOrderController;
use App\Http\Controllers\Api\Mobile\MobilePaymentLinkController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('mobile')->group(function () {
    Route::post('auth/login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [MobileAuthController::class, 'logout']);
        Route::post('auth/change-password', [MobileAuthController::class, 'changePassword']);

        Route::get('orders', [MobileOrderController::class, 'index']);
        Route::get('orders/{order}', [MobileOrderController::class, 'show']);
        Route::get('referred-clients', [MobileOrderController::class, 'referredClients']);
        Route::get('referred-clients/{client}/orders', [MobileOrderController::class, 'referredClientOrders']);
        Route::post('payment-link', [MobilePaymentLinkController::class, 'store']);
    });
});
