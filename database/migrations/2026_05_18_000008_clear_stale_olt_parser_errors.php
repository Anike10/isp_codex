<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('olt_devices')
            ->where('last_error', 'like', '%Out of range value for column%pon_port%')
            ->update([
                'last_error' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
