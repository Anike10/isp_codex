<?php

use App\Http\Controllers\BkashSmsPaymentController;
use Illuminate\Support\Facades\Route;

Route::post('bkash/sms', [BkashSmsPaymentController::class, 'store'])->name('api.bkash-sms.store');
