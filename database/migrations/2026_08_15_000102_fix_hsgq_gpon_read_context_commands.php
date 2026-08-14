<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('olt_protocol_profiles') && Schema::hasColumn('olt_protocol_profiles', 'default_read_context_commands')) {
            DB::table('olt_protocol_profiles')
                ->where('key', 'hsgq_gpon')
                ->update([
                    'default_read_context_commands' => "enable\nconfig",
                    'updated_at' => now(),
                ]);
        }

        if (! Schema::hasTable('olt_devices') || ! Schema::hasColumn('olt_devices', 'read_context_commands')) {
            return;
        }

        DB::table('olt_devices')
            ->where('protocol_profile', 'hsgq_gpon')
            ->select(['id', 'read_context_commands'])
            ->orderBy('id')
            ->get()
            ->each(function (object $device): void {
                $commands = array_values(array_filter(
                    array_map('trim', preg_split('/\R/', (string) $device->read_context_commands) ?: []),
                    fn (string $command): bool => $command !== ''
                ));

                if (! collect($commands)->contains(fn (string $command): bool => strtolower($command) === 'enable')) {
                    array_unshift($commands, 'enable');
                }

                if (! collect($commands)->contains(fn (string $command): bool => in_array(strtolower($command), ['config', 'configure'], true))) {
                    $commands[] = 'config';
                }

                DB::table('olt_devices')
                    ->where('id', $device->id)
                    ->update(['read_context_commands' => implode("\n", $commands)]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('olt_protocol_profiles') && Schema::hasColumn('olt_protocol_profiles', 'default_read_context_commands')) {
            DB::table('olt_protocol_profiles')
                ->where('key', 'hsgq_gpon')
                ->where('default_read_context_commands', "enable\nconfig")
                ->update([
                    'default_read_context_commands' => 'config',
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('olt_devices') && Schema::hasColumn('olt_devices', 'read_context_commands')) {
            DB::table('olt_devices')
                ->where('protocol_profile', 'hsgq_gpon')
                ->where('read_context_commands', "enable\nconfig")
                ->update(['read_context_commands' => 'config']);
        }
    }
};
