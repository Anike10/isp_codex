<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('service_valid_from')->nullable()->after('grace_used_at');
            $table->date('service_valid_until')->nullable()->after('service_valid_from');
            $table->text('service_validity_note')->nullable()->after('service_valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['service_valid_from', 'service_valid_until', 'service_validity_note']);
        });
    }
};
