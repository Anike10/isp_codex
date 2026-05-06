<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_routers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address')->unique();
            $table->unsignedSmallInteger('api_port')->default(8728);
            $table->string('username');
            $table->text('password');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'manage_mikrotik_routers',
            'label' => 'Manage MikroTik routers',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        if ($adminRoleId) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }

        DB::table('mikrotik_routers')->insert([
            'name' => 'Main MikroTik',
            'ip_address' => '192.168.6.1',
            'api_port' => 8728,
            'username' => 'admin',
            'password' => Crypt::encryptString('anikebd123'),
            'status' => 'active',
            'notes' => 'Default router added from local setup.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'manage_mikrotik_routers')->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('mikrotik_routers');
    }
};
