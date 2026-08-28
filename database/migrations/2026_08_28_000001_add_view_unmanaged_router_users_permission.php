<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $name = 'view_unmanaged_router_users';

    private string $label = 'See router PPPoE users missing from the app';

    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('name', $this->name)->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $this->name,
                'label' => $this->label,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        if ($adminRoleId && ! DB::table('permission_role')
            ->where('permission_id', $permissionId)
            ->where('role_id', $adminRoleId)
            ->exists()) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', $this->name)->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        DB::table('permission_user')->where('permission_id', $permissionId)->delete();
        DB::table('permission_user_denials')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
