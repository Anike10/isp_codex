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
        'parent_splitter',
        'port_vlans',
        'learned_macs',
        'rx_power_dbm',
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
            'learned_macs' => 'array',
            'rx_power_dbm' => 'decimal:2',
            'distance_m' => 'integer',
            'last_backup_at' => 'datetime',
            'last_live_polled_at' => 'datetime',
            'last_registered_at' => 'datetime',
            'last_deregistered_at' => 'datetime',
        ];
    }

    public function oltDevice()
    {
        return $this->belongsTo(OltDevice::class);
    }
}
