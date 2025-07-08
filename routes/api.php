<?php

use App\Http\Controllers\PaymentController;

Route::prefix('paymongo')->group(function () {
    Route::get('payment-methods', [PaymentController::class, 'getPaymentMethods']);
    Route::post('checkout-session', [PaymentController::class, 'checkoutSession']);
});