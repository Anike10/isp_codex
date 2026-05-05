<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentAccountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:view_dashboard')->name('dashboard');

    Route::middleware('permission:manage_customers')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    });

    Route::middleware('permission:manage_packages')->group(function () {
        Route::resource('packages', PackageController::class)->only(['index', 'show', 'create', 'store']);
    });

    Route::middleware('permission:manage_invoices')->group(function () {
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::get('invoice-customers/search', [InvoiceController::class, 'searchCustomers'])->name('invoice-customers.search');
        Route::post('invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::get('invoices/{invoice}/challan', [InvoiceController::class, 'challan'])->name('invoices.challan');
        Route::get('invoices/{invoice}/quotation', [InvoiceController::class, 'quotation'])->name('invoices.quotation');
        Route::get('invoices/{invoice}/delivery-challan', [InvoiceController::class, 'deliveryChallan'])->name('invoices.delivery-challan');
    });

    Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])
        ->middleware('permission:finalize_invoices')
        ->name('invoices.finalize');

    Route::middleware('permission:manage_payments')->group(function () {
        Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store']);
    });

    Route::middleware('permission:manage_payment_accounts')->group(function () {
        Route::get('payment-accounts/cash/ledger', [PaymentAccountController::class, 'cashLedger'])->name('payment-accounts.cash-ledger');
        Route::resource('payment-accounts', PaymentAccountController::class)->only(['index', 'show', 'create', 'store']);
    });

    Route::middleware('permission:manage_tickets')->group(function () {
        Route::resource('tickets', TicketController::class)->only(['index', 'show', 'create', 'store']);
    });

    Route::middleware('permission:manage_products')->group(function () {
        Route::resource('products', ProductController::class)->only(['index', 'show', 'create', 'store']);
        Route::post('products/{product}/stock', [ProductController::class, 'moveStock'])->name('products.stock');
    });

    Route::middleware('permission:manage_users')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('roles', RoleController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    });

    Route::get('backup/database', [DatabaseBackupController::class, 'download'])
        ->middleware('permission:download_backup')
        ->name('backup.database');
});
