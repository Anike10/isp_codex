<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            // The saved API user can only read RouterOS. The app may still
            // import every secret and profile, but must not push changes back.
            $table->boolean('read_only')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->dropColumn('read_only');
        });
    }
};
