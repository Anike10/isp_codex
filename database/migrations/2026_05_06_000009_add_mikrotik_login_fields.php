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
        Schema::table('internet_packages', function (Blueprint $table) {
            $table->string('mikrotik_profile')->nullable()->after('speed');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('mikrotik_username')->nullable()->after('connection_id');
            $table->text('mikrotik_password')->nullable()->after('mikrotik_username');
        });

        DB::table('internet_packages')->orderBy('id')->get(['id', 'name'])->each(function ($package): void {
            DB::table('internet_packages')->where('id', $package->id)->update([
                'mikrotik_profile' => $package->name,
            ]);
        });

        DB::table('customers')->orderBy('id')->get(['id', 'connection_id'])->each(function ($customer): void {
            DB::table('customers')->where('id', $customer->id)->update([
                'mikrotik_username' => $customer->connection_id,
                'mikrotik_password' => Crypt::encryptString('4321'),
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('mikrotik_username');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['mikrotik_username']);
            $table->dropColumn(['mikrotik_username', 'mikrotik_password']);
        });

        Schema::table('internet_packages', function (Blueprint $table) {
            $table->dropColumn('mikrotik_profile');
        });
    }
};
