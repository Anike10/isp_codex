<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\OltDevice;
use App\Models\OltOnu;
use App\Models\Product;
use App\Models\RecordVersion;
use Illuminate\Database\Eloquent\Model;

class RecordVersionObserver
{
    private static int $suppressionDepth = 0;

    /**
     * High-frequency telemetry columns written by background polling / sync,
     * never by a human editing the record. When an update touches ONLY these,
     * no audit row is written — otherwise every status poll and every sync run
     * would flood `record_versions` (and bloat DB backups).
     *
     * @var array<class-string, list<string>>
     */
    private const IGNORED_FIELDS = [
        Product::class => ['stock_quantity'],
        Customer::class => [
            'last_connected_ip', 'last_connected_mac', 'last_connected_at',
            'learned_ip_address', 'learned_ip_package_id',
        ],
        MikrotikRouter::class => [
            'last_api_status', 'last_ping_status', 'last_api_latency_ms', 'last_ping_latency_ms',
            'last_checked_at', 'last_online_at', 'last_offline_at', 'last_ping_at',
            'last_connection_message', 'last_pppoe_sync_at', 'last_pppoe_sync_summary',
            'last_active_mac_sync_at', 'last_active_mac_sync_summary',
            'api_status_since', 'ping_status_since',
        ],
        OltOnu::class => [
            'rx_power_dbm', 'tx_power_dbm', 'last_live_polled_at', 'last_backup_at',
            'last_registered_at', 'last_deregistered_at', 'last_deregister_reason',
            'raw_live_output', 'raw_interface_config', 'raw_bind_config',
        ],
        OltDevice::class => ['last_polled_at', 'last_error', 'last_raw_output'],
    ];

    public static function withoutRecording(callable $callback): mixed
    {
        self::$suppressionDepth++;

        try {
            return $callback();
        } finally {
            self::$suppressionDepth--;
        }
    }

    public function updated(Model $model): void
    {
        if (self::$suppressionDepth > 0) {
            return;
        }

        $ignoredFields = array_merge(['updated_at'], self::IGNORED_FIELDS[$model::class] ?? []);

        $dirty = collect($model->getChanges())->except($ignoredFields)->all();

        if ($dirty === []) {
            return;
        }

        $user = auth()->user();
        $changedFields = array_keys($dirty);
        $oldValues = [];
        $newValues = [];

        foreach ($changedFields as $field) {
            $oldValues[$field] = $this->isSensitiveField($field) ? '[hidden]' : $this->normalizeValue($model->getOriginal($field));
            $newValues[$field] = $this->isSensitiveField($field) ? '[hidden]' : $this->normalizeValue($model->getAttribute($field));
        }

        RecordVersion::create([
            'versionable_type' => $model::class,
            'versionable_id' => $model->getKey(),
            'table_name' => $model->getTable(),
            'action' => 'updated',
            'edited_by' => $user ? (string) $user->id : 'system',
            'edited_by_type' => $user ? 'user' : 'system',
            'edited_by_name' => $user?->name ?? 'System',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'metadata' => [
                'source' => 'model_update',
            ],
        ]);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    private function isSensitiveField(string $field): bool
    {
        foreach (['password', 'token', 'secret', 'key'] as $needle) {
            if (str_contains(strtolower($field), $needle)) {
                return true;
            }
        }

        return false;
    }
}
