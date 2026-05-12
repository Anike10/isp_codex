<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'manage_expenses')->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'manage_expenses',
                'label' => 'Manage salaries and expenses',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        if ($adminRoleId && ! DB::table('permission_role')->where('permission_id', $permissionId)->where('role_id', $adminRoleId)->exists()) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'manage_expenses')->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
