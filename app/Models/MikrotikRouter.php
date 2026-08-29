<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class MikrotikRouter extends Model
{
    use HasFactory;

    public const API_PASSWORD_REENTRY_MESSAGE = 'The saved RouterOS API password cannot be decrypted with this app key. Re-enter the API password from Edit MikroTik Router.';

    protected $fillable = [
        'entry_by',
        'name',
        'ip_address',
        'api_port',
        'pppoe_sync_interval_minutes',
        'username',
        'password',
        'status',
        'read_only',
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
            'read_only' => 'boolean',
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

    public function apiPassword(): string
    {
        try {
            $password = (string) $this->password;
        } catch (DecryptException $exception) {
            throw new RuntimeException(self::API_PASSWORD_REENTRY_MESSAGE, previous: $exception);
        }

        if ($password === '') {
            throw new RuntimeException(self::API_PASSWORD_REENTRY_MESSAGE);
        }

        return $password;
    }

    public function requiresApiPasswordReentry(): bool
    {
        try {
            $this->apiPassword();

            return false;
        } catch (RuntimeException) {
            return true;
        }
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

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class)->withTimestamps();
    }
}
