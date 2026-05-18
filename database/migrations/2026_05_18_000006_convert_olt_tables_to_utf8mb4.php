<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE olt_devices CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::statement('ALTER TABLE olt_onus CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE olt_devices CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::statement('ALTER TABLE olt_onus CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }
};
