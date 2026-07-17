<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OltProtocolProfile extends Model
{
    protected $fillable = [
        'key',
        'label',
        'brand',
        'pon_interface_command',
        'onu_context_command',
        'supports_vlan_polling',
        'supports_mac_polling',
        'default_read_context_commands',
        'default_onu_status_command',
        'default_onu_power_command',
        'default_onu_alarm_command',
        'default_onu_vlan_command',
        'default_onu_mac_command',
        'vlan_write_context_command',
        'vlan_write_command',
        'port_admin_context_command',
        'port_admin_command',
        'save_config_command',
    ];

    protected function casts(): array
    {
        return [
            'supports_vlan_polling' => 'boolean',
            'supports_mac_polling' => 'boolean',
        ];
    }
}
