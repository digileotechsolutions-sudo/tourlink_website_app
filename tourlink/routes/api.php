<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\MpesaPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/mpesa/callback', MpesaCallbackController::class)
    ->middleware('throttle:mpesa-callback')
    ->name('mpesa.callback');

Route::middleware(['auth:sanctum', 'account.access'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum')
    ->name('api.logout');
Route::post('/payments/mpesa/stk', [MpesaPaymentController::class, 'stk'])
    ->middleware(['auth:sanctum', 'account.access:TRAVELER,OPERATOR,VEHICLE_OWNER', 'throttle:payments'])
    ->name('payments.mpesa.stk');
Route::get('/pesapal/ipn', \App\Http\Controllers\PesapalNotificationController::class)
    ->middleware('throttle:60,1')
    ->name('pesapal.ipn');
