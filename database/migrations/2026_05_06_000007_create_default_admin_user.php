<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $userId = DB::table('users')->where('email', 'admin@example.com')->value('id');

        if (! $userId) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        if ($adminRoleId && ! DB::table('role_user')->where('role_id', $adminRoleId)->where('user_id', $userId)->exists()) {
            DB::table('role_user')->insert([
                'role_id' => $adminRoleId,
                'user_id' => $userId,
            ]);
        }
    }

    public function down(): void
    {
        $userId = DB::table('users')->where('email', 'admin@example.com')->value('id');

        if ($userId) {
            DB::table('role_user')->where('user_id', $userId)->delete();
            DB::table('permission_user')->where('user_id', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
        }
    }
};
