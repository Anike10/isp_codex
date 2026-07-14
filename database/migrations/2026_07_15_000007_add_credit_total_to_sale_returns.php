<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table): void {
            $table->decimal('credit_total', 14, 2)->default(0)->after('subtotal');
        });

        DB::table('sale_returns')->update([
            'credit_total' => DB::raw('invoice_credit_amount + advance_credit_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table): void {
            $table->dropColumn('credit_total');
        });
    }
};
