<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            'key' => 'invoice_payment_note',
            'value' => 'Please pay the due amount by the due date. Keep this bill for your records.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->text('payment_note')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_note');
        });

        Schema::dropIfExists('app_settings');
    }
};
