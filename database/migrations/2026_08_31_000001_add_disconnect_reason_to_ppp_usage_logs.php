<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppp_usage_logs', function (Blueprint $table): void {
            // RouterOS PPP on-down $"last-disconnect-reason" (e.g. "hang-up",
            // "peer-request", "term-req", "session timeout"). Nullable — only
            // rows logged after the updated on-down script is pushed have it.
            $table->string('disconnect_reason')->nullable()->after('caller_id');
        });
    }

    public function down(): void
    {
        Schema::table('ppp_usage_logs', function (Blueprint $table): void {
            $table->dropColumn('disconnect_reason');
        });
    }
};
