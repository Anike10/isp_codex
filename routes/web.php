<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentAccountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
Route::resource('packages', PackageController::class)->only(['index', 'create', 'store']);
Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
Route::get('invoice-customers/search', [InvoiceController::class, 'searchCustomers'])->name('invoice-customers.search');
Route::post('invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
Route::get('invoices/{invoice}/challan', [InvoiceController::class, 'challan'])->name('invoices.challan');
Route::get('invoices/{invoice}/quotation', [InvoiceController::class, 'quotation'])->name('invoices.quotation');
Route::get('invoices/{invoice}/delivery-challan', [InvoiceController::class, 'deliveryChallan'])->name('invoices.delivery-challan');
Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store']);
Route::resource('payment-accounts', PaymentAccountController::class)->only(['index', 'show', 'create', 'store']);
Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store']);
Route::resource('products', ProductController::class)->only(['index', 'create', 'store']);
Route::post('products/{product}/stock', [ProductController::class, 'moveStock'])->name('products.stock');
