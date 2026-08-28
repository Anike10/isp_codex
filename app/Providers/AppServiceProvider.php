<?php

namespace App\Providers;

use App\Models\AccountDeposit;
use App\Models\AppSetting;
use App\Models\BkashSmsPayment;
use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\Employee;
use App\Models\EmployeeSalaryRevision;
use App\Models\Expense;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MikrotikRouter;
use App\Models\OltDevice;
use App\Models\OltOnu;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentAllocation;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimLog;
use App\Observers\EntryByObserver;
use App\Observers\InternetPackageIpPoolObserver;
use App\Observers\RecordVersionObserver;
use App\Observers\SubscriptionIpObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

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

        View::composer(['layouts.app', 'auth.login'], function ($view) {
            $view->with('appOrganization', Schema::hasTable('organizations') ? Organization::defaultOrganization() : null);
        });

        foreach ([
            AccountDeposit::class,
            BkashSmsPayment::class,
            Customer::class,
            CustomerBalanceTransaction::class,
            InternetPackage::class,
            Invoice::class,
            InvoiceItem::class,
            MikrotikRouter::class,
            OltDevice::class,
            OltOnu::class,
            Payment::class,
            PaymentAccount::class,
            PaymentAllocation::class,
            Permission::class,
            Product::class,
            ProductCategory::class,
            ProductSerial::class,
            PurchaseBill::class,
            PurchaseBillItem::class,
            Role::class,
            StockMovement::class,
            Subscription::class,
            SupportTicket::class,
            User::class,
        ] as $model) {
            $model::observe(EntryByObserver::class);
        }

        foreach ([
            AppSetting::class,
            Customer::class,
            Employee::class,
            EmployeeSalaryRevision::class,
            Expense::class,
            InternetPackage::class,
            Invoice::class,
            InvoiceItem::class,
            Payment::class,
            PaymentAccount::class,
            Product::class,
            ProductCategory::class,
            Quotation::class,
            QuotationItem::class,
            PurchaseBill::class,
            PurchaseBillItem::class,
            Role::class,
            Subscription::class,
            SupportTicket::class,
            User::class,
            Warehouse::class,
            WarrantyClaim::class,
            WarrantyClaimLog::class,
        ] as $model) {
            $model::observe(RecordVersionObserver::class);
        }

        InternetPackage::observe(InternetPackageIpPoolObserver::class);
        Subscription::observe(SubscriptionIpObserver::class);
    }
}
