<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['payment_account_id', 'payment_date', 'id'], 'payments_account_ledger_index');
            $table->index(['payment_method', 'payment_date', 'id'], 'payments_method_ledger_index');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->index(['payment_account_id', 'expense_date', 'id'], 'expenses_account_ledger_index');
            $table->index(['payment_method', 'expense_date', 'id'], 'expenses_method_ledger_index');
        });

        Schema::table('customer_balance_transactions', function (Blueprint $table): void {
            $table->index(['payment_account_id', 'direction', 'payment_id', 'transaction_date', 'id'], 'balance_transactions_account_ledger_index');
            $table->index(['payment_method', 'direction', 'payment_id', 'transaction_date', 'id'], 'balance_transactions_method_ledger_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_account_ledger_index');
            $table->dropIndex('payments_method_ledger_index');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_account_ledger_index');
            $table->dropIndex('expenses_method_ledger_index');
        });

        Schema::table('customer_balance_transactions', function (Blueprint $table): void {
            $table->dropIndex('balance_transactions_account_ledger_index');
            $table->dropIndex('balance_transactions_method_ledger_index');
        });
    }
};
