<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            ['show ont-infoall', 'show ont-info all'],
            ['show onu-infoall', 'show onu-info all'],
        ];

        $this->updateStatusCommandFormats('olt_devices', 'onu_status_command', $replacements);
        $this->updateStatusCommandFormats('olt_protocol_profiles', 'default_onu_status_command', $replacements);
    }

    public function down(): void
    {
        $replacements = [
            ['show ont-info all', 'show ont-infoall'],
            ['show onu-info all', 'show onu-infoall'],
        ];

        $this->updateStatusCommandFormats('olt_devices', 'onu_status_command', $replacements);
        $this->updateStatusCommandFormats('olt_protocol_profiles', 'default_onu_status_command', $replacements);
    }

    private function updateStatusCommandFormats(string $table, string $column, array $replacements): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        foreach ($replacements as [$old, $new]) {
            DB::table($table)
                ->where($column, 'like', '%' . $old . '%')
                ->update([$column => DB::raw("REPLACE(`$column`, '$old', '$new')")]);
        }
    }
};
