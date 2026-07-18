<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_reseller')->default(false)->after('is_vendor');
            $table->foreignId('reseller_id')->nullable()->after('is_reseller')->constrained('customers')->nullOnDelete();
            $table->decimal('reseller_daily_payment_limit', 12, 2)->nullable()->after('reseller_id');
            $table->index(['is_reseller', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->after('password')->constrained('customers')->nullOnDelete();
        });

        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreignId('funded_by_customer_id')->nullable()->after('customer_id')->constrained('customers')->nullOnDelete();
            $table->uuid('operation_key')->nullable()->after('source_type')->unique();
        });

        Schema::table('customer_balance_transactions', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('payment_id')->constrained()->nullOnDelete();
            $table->uuid('operation_key')->nullable()->after('reference')->unique();
        });

        $now = now();
        $managePermissionId = DB::table('permissions')->insertGetId([
            'name' => 'manage_resellers',
            'label' => 'Manage resellers and wallets',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $portalPermissionId = DB::table('permissions')->insertGetId([
            'name' => 'use_reseller_portal',
            'label' => 'Use reseller portal',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        if ($adminRoleId) {
            DB::table('permission_role')->insert([
                ['permission_id' => $managePermissionId, 'role_id' => $adminRoleId],
                ['permission_id' => $portalPermissionId, 'role_id' => $adminRoleId],
            ]);
        }

        $resellerRoleId = DB::table('roles')->insertGetId([
            'name' => 'reseller',
            'label' => 'Reseller',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('permission_role')->insert([
            'permission_id' => $portalPermissionId,
            'role_id' => $resellerRoleId,
        ]);
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['manage_resellers', 'use_reseller_portal'])
            ->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permission_user')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        $resellerRoleId = DB::table('roles')->where('name', 'reseller')->value('id');
        if ($resellerRoleId) {
            DB::table('role_user')->where('role_id', $resellerRoleId)->delete();
            DB::table('roles')->where('id', $resellerRoleId)->delete();
        }

        Schema::table('customer_balance_transactions', function (Blueprint $table) {
            $table->dropUnique(['operation_key']);
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropColumn('operation_key');
        });
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropUnique(['operation_key']);
            $table->dropConstrainedForeignId('funded_by_customer_id');
            $table->dropColumn('operation_key');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('reseller_id'));
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['is_reseller', 'status']);
            $table->dropConstrainedForeignId('reseller_id');
            $table->dropColumn(['is_reseller', 'reseller_daily_payment_limit']);
        });
    }
};
