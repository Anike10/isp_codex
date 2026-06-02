<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OltDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'name',
        'brand',
        'protocol_profile',
        'host',
        'access_method',
        'port',
        'username',
        'password',
        'enable_password',
        'snmp_community',
        'snmp_enabled',
        'snmp_version',
        'snmp_port',
        'snmp_community',
        'snmp_timeout_ms',
        'snmp_retries',
        'snmp_status_oid_template',
        'snmp_power_oid_template',
        'snmp_power_divisor',
        'read_context_commands',
        'pon_ports',
        'onu_status_command',
        'onu_power_command',
        'onu_alarm_command',
        'onu_vlan_command',
        'onu_mac_command',
        'status',
        'last_polled_at',
        'last_error',
        'last_raw_output',
        'notes',
    ];

    protected $hidden = [
        'password',
        'enable_password',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'password' => 'encrypted',
            'enable_password' => 'encrypted',
            'snmp_community' => 'encrypted',
            'snmp_enabled' => 'boolean',
            'snmp_port' => 'integer',
            'snmp_timeout_ms' => 'integer',
            'snmp_retries' => 'integer',
            'snmp_power_divisor' => 'decimal:2',
            'last_polled_at' => 'datetime',
        ];
    }

    public function onus(): HasMany
    {
        return $this->hasMany(OltOnu::class);
    }
}
