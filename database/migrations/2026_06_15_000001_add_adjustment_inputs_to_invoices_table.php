<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('amount')->after('discount');
            $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            $table->string('vat_type', 20)->default('amount')->after('vat');
            $table->decimal('vat_value', 10, 2)->default(0)->after('vat_type');
        });

        DB::table('invoices')->update([
            'discount_value' => DB::raw('discount'),
            'vat_value' => DB::raw('vat'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type',
                'discount_value',
                'vat_type',
                'vat_value',
            ]);
        });
    }
};
