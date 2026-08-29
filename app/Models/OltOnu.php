<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OltOnu extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'olt_device_id',
        'olt_name',
        'pon_port',
        'onu_id',
        'mac_address',
        'onu_type',
        'status',
        'name',
        'description',
        'note',
        'parent_splitter',
        'port_vlans',
        'port_admin_states',
        'ethernet_port_count',
        'learned_macs',
        'rx_power_dbm',
        'tx_power_dbm',
        'power_note',
        'distance_m',
        'raw_bind_config',
        'raw_interface_config',
        'raw_live_output',
        'last_backup_at',
        'last_live_polled_at',
        'last_registered_at',
        'last_deregistered_at',
        'last_deregister_reason',
    ];

    protected function casts(): array
    {
        return [
            'pon_port' => 'integer',
            'onu_id' => 'integer',
            'port_vlans' => 'array',
            'port_admin_states' => 'array',
            'ethernet_port_count' => 'integer',
            'learned_macs' => 'array',
            'rx_power_dbm' => 'decimal:2',
            'tx_power_dbm' => 'decimal:2',
            'distance_m' => 'integer',
            'last_backup_at' => 'datetime',
            'last_live_polled_at' => 'datetime',
            'last_registered_at' => 'datetime',
            'last_deregistered_at' => 'datetime',
        ];
    }

    public function ethernetPortNumbers(): array
    {
        if ($this->ethernet_port_count !== null) {
            return $this->ethernet_port_count > 0 ? range(1, $this->ethernet_port_count) : [];
        }

        if ($this->oltDevice?->protocol_profile === 'hsgq_gpon' && str_contains(strtoupper((string) $this->onu_type), 'HGU')) {
            return [];
        }

        $ports = collect($this->port_vlans ?: [])
            ->pluck('port')
            ->merge(array_keys($this->port_admin_states ?: []))
            ->map(fn ($port): int => (int) $port)
            ->filter(fn (int $port): bool => $port >= 1 && $port <= 32);
        $highestPort = (int) ($ports->max() ?: 1);

        return range(1, $highestPort);
    }

    public function ethernetPortState(int $port): string
    {
        $state = strtolower((string) data_get($this->port_admin_states ?: [], (string) $port, 'enabled'));

        return in_array($state, ['disable', 'disabled', 'down', 'inactive'], true) ? 'disabled' : 'enabled';
    }

    public function oltDevice()
    {
        return $this->belongsTo(OltDevice::class);
    }

    public function displayName(): ?string
    {
        $name = trim((string) $this->name);

        if ($name !== '') {
            return $name;
        }

        foreach ($this->learned_macs ?: [] as $learnedMac) {
            $learnedName = trim((string) ($learnedMac['onu_name'] ?? ''));

            if ($learnedName !== '') {
                return $learnedName;
            }
        }

        return null;
    }
}
