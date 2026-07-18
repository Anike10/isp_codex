<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('use_fixed_ip')->default(false)->after('mikrotik_router_id');
            $table->string('fixed_ip_address', 45)->nullable()->after('use_fixed_ip');
            $table->string('learned_ip_address', 45)->nullable()->after('fixed_ip_address');
            $table->foreignId('learned_ip_package_id')->nullable()->after('learned_ip_address')
                ->constrained('internet_packages')->nullOnDelete();
            $table->string('last_connected_ip', 45)->nullable()->after('learned_ip_package_id');
            $table->string('last_connected_mac', 64)->nullable()->after('last_connected_ip');
            $table->timestamp('last_connected_at')->nullable()->after('last_connected_mac');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['learned_ip_package_id']);
            $table->dropColumn([
                'use_fixed_ip',
                'fixed_ip_address',
                'learned_ip_address',
                'learned_ip_package_id',
                'last_connected_ip',
                'last_connected_mac',
                'last_connected_at',
            ]);
        });
    }
};
