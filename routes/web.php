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

Route::post('/xendit/webhook', XenditWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('xendit.webhook');
