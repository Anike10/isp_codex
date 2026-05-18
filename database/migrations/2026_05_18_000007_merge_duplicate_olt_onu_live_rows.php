<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $liveRows = DB::table('olt_onus')
                ->whereNotNull('olt_device_id')
                ->orderBy('id')
                ->get();

            foreach ($liveRows as $liveRow) {
                $baseRow = DB::table('olt_onus')
                    ->whereNull('olt_device_id')
                    ->where('pon_port', $liveRow->pon_port)
                    ->where('onu_id', $liveRow->onu_id)
                    ->first();

                if (! $baseRow) {
                    continue;
                }

                $updates = [
                    'olt_device_id' => $liveRow->olt_device_id,
                    'olt_name' => $liveRow->olt_name ?: $baseRow->olt_name,
                    'last_live_polled_at' => $liveRow->last_live_polled_at,
                    'updated_at' => now(),
                ];

                foreach ([
                    'mac_address',
                    'status',
                    'name',
                    'description',
                    'rx_power_dbm',
                    'distance_m',
                    'raw_live_output',
                    'last_registered_at',
                    'last_deregistered_at',
                    'last_deregister_reason',
                ] as $field) {
                    if (! property_exists($liveRow, $field)) {
                        continue;
                    }

                    if ($liveRow->{$field} !== null && $liveRow->{$field} !== '') {
                        $updates[$field] = $liveRow->{$field};
                    }
                }

                DB::table('olt_onus')->where('id', $liveRow->id)->delete();
                DB::table('olt_onus')->where('id', $baseRow->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        //
    }
};
