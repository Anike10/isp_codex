<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_onus', function (Blueprint $table) {
            $table->timestamp('last_registered_at')->nullable()->after('last_live_polled_at')->index();
            $table->timestamp('last_deregistered_at')->nullable()->after('last_registered_at')->index();
            $table->string('last_deregister_reason')->nullable()->after('last_deregistered_at');
        });
    }

    public function down(): void
    {
        Schema::table('olt_onus', function (Blueprint $table) {
            $table->dropColumn(['last_registered_at', 'last_deregistered_at', 'last_deregister_reason']);
        });
    }
};
