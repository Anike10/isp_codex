<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_imported_secrets', function (Blueprint $table): void {
            // Device MAC (caller-id) of the live /ppp/active session for this
            // user, captured whenever the router is polled.
            $table->string('device_mac')->nullable()->after('remote_address');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_imported_secrets', function (Blueprint $table): void {
            $table->dropColumn('device_mac');
        });
    }
};
