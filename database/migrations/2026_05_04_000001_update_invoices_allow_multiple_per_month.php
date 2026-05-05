<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('customer_id');
            $table->dropUnique(['customer_id', 'billing_month']);
            $table->string('invoice_type')->default('service')->after('billing_month');
            $table->unique(['customer_id', 'billing_month', 'invoice_type']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'billing_month', 'invoice_type']);
            $table->dropIndex(['customer_id']);
            $table->dropColumn('invoice_type');
            $table->unique(['customer_id', 'billing_month']);
        });
    }
};
