<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table): void {
            $table->decimal('invoice_credit_amount', 14, 2)->default(0)->after('subtotal');
            $table->decimal('advance_credit_amount', 14, 2)->default(0)->after('invoice_credit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table): void {
            $table->dropColumn(['invoice_credit_amount', 'advance_credit_amount']);
        });
    }
};
