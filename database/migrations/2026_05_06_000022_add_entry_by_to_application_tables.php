<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'users',
        'roles',
        'permissions',
        'internet_packages',
        'customers',
        'subscriptions',
        'invoices',
        'invoice_items',
        'payments',
        'payment_accounts',
        'support_tickets',
        'products',
        'stock_movements',
        'mikrotik_routers',
        'bkash_sms_payments',
        'payment_allocations',
        'customer_balance_transactions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'entry_by')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('entry_by')->nullable()->after('id')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'entry_by')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('entry_by');
            });
        }
    }
};
