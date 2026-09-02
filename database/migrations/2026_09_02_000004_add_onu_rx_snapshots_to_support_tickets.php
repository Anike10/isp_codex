<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot the party's latest ONU Rx laser power onto each support ticket:
 * once when the ticket is created, and again on every status/reply update,
 * so the ticket list can show how the signal moved while the case was open.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->decimal('rx_power_on_create', 6, 2)->nullable()->after('status');
            $table->decimal('rx_power_on_update', 6, 2)->nullable()->after('rx_power_on_create');
            $table->timestamp('rx_power_updated_at')->nullable()->after('rx_power_on_update');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropColumn(['rx_power_on_create', 'rx_power_on_update', 'rx_power_updated_at']);
        });
    }
};
