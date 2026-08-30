<?php

use App\Models\OltOnu;
use App\Services\OltLiveOutputParser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_devices', function (Blueprint $table): void {
            // Optional SNMP OID for the ONU/ONT upstream transmit power, so a
            // quick (SNMP) refresh can capture Tx like it already does Rx.
            // Shares snmp_power_divisor for scaling.
            $table->string('snmp_tx_power_oid_template')->nullable()->after('snmp_power_oid_template');
        });

        // Backfill Tx (and any missing Rx) for ONUs already polled over CLI —
        // their raw_live_output carries the reading, the parser just never
        // wrote it before, or a later SNMP-only refresh left it blank.
        if (! Schema::hasColumn('olt_onus', 'tx_power_dbm')) {
            return;
        }

        $parser = new OltLiveOutputParser;

        OltOnu::query()
            ->whereNull('tx_power_dbm')
            ->whereNotNull('raw_live_output')
            ->where('raw_live_output', 'not like', 'SNMP%')
            ->chunkById(200, function ($onus) use ($parser): void {
                foreach ($onus as $onu) {
                    foreach ($parser->parse((string) $onu->raw_live_output) as $record) {
                        if ((int) ($record['pon_port'] ?? -1) !== (int) $onu->pon_port
                            || (int) ($record['onu_id'] ?? -1) !== (int) $onu->onu_id) {
                            continue;
                        }

                        $update = [];
                        if (array_key_exists('tx_power_dbm', $record)) {
                            $update['tx_power_dbm'] = $record['tx_power_dbm'];
                        }
                        if ($onu->rx_power_dbm === null && array_key_exists('rx_power_dbm', $record)) {
                            $update['rx_power_dbm'] = $record['rx_power_dbm'];
                        }

                        if ($update !== []) {
                            $onu->forceFill($update)->saveQuietly();
                        }
                        break;
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table): void {
            $table->dropColumn('snmp_tx_power_oid_template');
        });
    }
};
