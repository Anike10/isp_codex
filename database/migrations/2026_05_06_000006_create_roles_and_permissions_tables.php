<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'user_id']);
        });

        $permissions = [
            'view_dashboard' => 'View dashboard',
            'manage_customers' => 'Manage customers',
            'manage_packages' => 'Manage packages',
            'manage_invoices' => 'Manage invoices',
            'finalize_invoices' => 'Finalize invoices',
            'manage_payments' => 'Manage payments',
            'manage_payment_accounts' => 'Manage payment accounts',
            'manage_tickets' => 'Manage tickets',
            'manage_products' => 'Manage inventory',
            'manage_users' => 'Manage users and permissions',
            'download_backup' => 'Download database backup',
        ];

        foreach ($permissions as $name => $label) {
            DB::table('permissions')->insert([
                'name' => $name,
                'label' => $label,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'label' => 'Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('permissions')->pluck('id') as $permissionId) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }

        foreach (User::query()->pluck('id') as $userId) {
            DB::table('role_user')->insert([
                'role_id' => $adminRoleId,
                'user_id' => $userId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
