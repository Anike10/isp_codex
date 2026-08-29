<?php

use App\Http\Controllers\BkashSmsPaymentController;
use App\Http\Controllers\PppUsageWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('bkash/sms', [BkashSmsPaymentController::class, 'store'])->name('api.bkash-sms.store');

Route::post('ppp/usage', [PppUsageWebhookController::class, 'store'])->name('api.ppp-usage.store');
