<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Each privileged party action becomes its own permission so an admin can
     * hand them out one at a time from the user form.
     */
    private array $permissions = [
        'grant_grace_period' => 'Grant party grace period',
        'override_service_validity' => 'Force / override service validity date',
        'quick_activate_service' => 'Quick-activate service to next date',
        'force_service_status' => 'Force service active / inactive',
        'mark_special_customer' => 'Mark party as special (never suspend)',
        'view_concession_reports' => 'View concession & give-away reports',
    ];

    public function up(): void
    {
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        foreach ($this->permissions as $name => $label) {
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'label' => $label,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($this->permissions))
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permission_user')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permission_user_denials')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
