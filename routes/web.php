<?php

use App\Http\Controllers\GhlAppController;
use App\Http\Controllers\GhlWebhookController;
use App\Http\Controllers\XenditPaymentController;
use App\Http\Controllers\XenditWebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ghl/install', [GhlAppController::class, 'install'])->name('ghl.install');
Route::get('/ghl/callback', [GhlAppController::class, 'callback'])->name('ghl.callback');
Route::get('/ghl/dashboard', [GhlAppController::class, 'dashboard'])->name('ghl.dashboard');

Route::post('/ghl/webhook', GhlWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('ghl.webhook');

Route::post('/xendit/invoices', [XenditPaymentController::class, 'createInvoice'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('xendit.invoices.create');

Route::get('/xendit/test', [XenditPaymentController::class, 'simulatedCheckout'])
    ->name('xendit.test');

Route::get('/xendit/test/checkout/{invoiceId}', [XenditPaymentController::class, 'simulatedCheckout'])
    ->name('xendit.test.checkout');

Route::get('/xendit/payment-link', [XenditPaymentController::class, 'showPaymentLink'])
    ->name('xendit.payment-link');

Route::post('/xendit/payment-link', [XenditPaymentController::class, 'createPaymentLink'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('xendit.payment-link.create');

Route::get('/xendit/success', [XenditPaymentController::class, 'success'])
    ->name('xendit.success');

Route::get('/xendit/failed', [XenditPaymentController::class, 'failed'])
    ->name('xendit.failed');

Route::post('/pay-with-card', [XenditPaymentController::class, 'payWithCard'])
    ->name('xendit.pay-with-card');

Route::post('/pay-via-ewallet', [XenditPaymentController::class, 'payViaEwallet'])
    ->name('xendit.pay-via-ewallet');

Route::post('/pay-via-qr', [XenditPaymentController::class, 'payViaQr'])
    ->name('xendit.pay-via-qr');

Route::post('/pay-via-qr/simulate', [XenditPaymentController::class, 'simulateQrPayment'])
    ->name('xendit.pay-via-qr.simulate');

Route::post('/xendit/webhook', XenditWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('xendit.webhook');
