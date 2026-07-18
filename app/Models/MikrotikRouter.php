<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MikrotikRouter extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'name',
        'ip_address',
        'api_port',
        'pppoe_sync_interval_minutes',
        'username',
        'password',
        'status',
        'last_api_status',
        'last_ping_status',
        'last_api_latency_ms',
        'last_ping_latency_ms',
        'last_checked_at',
        'last_online_at',
        'last_offline_at',
        'last_ping_at',
        'last_connection_message',
        'last_pppoe_sync_at',
        'inactive_pppoe_profile',
        'last_pppoe_sync_summary',
        'notes',
        'api_status_since',
        'ping_status_since',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'api_port' => 'integer',
            'pppoe_sync_interval_minutes' => 'integer',
            'password' => 'encrypted',
            'last_api_latency_ms' => 'integer',
            'last_ping_latency_ms' => 'integer',
            'last_checked_at' => 'datetime',
            'last_online_at' => 'datetime',
            'last_offline_at' => 'datetime',
            'last_ping_at' => 'datetime',
            'last_pppoe_sync_at' => 'datetime',
            'api_status_since' => 'datetime',
            'ping_status_since' => 'datetime',
        ];
    }

    public function importedProfiles(): HasMany
    {
        return $this->hasMany(MikrotikImportedProfile::class);
    }

    public function importedIpPools(): HasMany
    {
        return $this->hasMany(MikrotikImportedIpPool::class);
    }

    public function importedSecrets(): HasMany
    {
        return $this->hasMany(MikrotikImportedSecret::class);
    }
}
