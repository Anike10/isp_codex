<?php

namespace App\Providers;

use App\Models\BkashSmsPayment;
use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MikrotikRouter;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentAllocation;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use App\Observers\EntryByObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.app');
        Paginator::defaultSimpleView('vendor.pagination.app');

        foreach ([
            BkashSmsPayment::class,
            Customer::class,
            CustomerBalanceTransaction::class,
            InternetPackage::class,
            Invoice::class,
            InvoiceItem::class,
            MikrotikRouter::class,
            Payment::class,
            PaymentAccount::class,
            PaymentAllocation::class,
            Permission::class,
            Product::class,
            Role::class,
            StockMovement::class,
            Subscription::class,
            SupportTicket::class,
            User::class,
        ] as $model) {
            $model::observe(EntryByObserver::class);
        }
    }
}
