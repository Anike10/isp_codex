<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PppLiveSession extends Model
{
    protected $fillable = [
        'mikrotik_router_id',
        'customer_id',
        'routeros_session_id',
        'username',
        'caller_id',
        'uptime',
        'uptime_seconds',
        'download_bytes',
        'upload_bytes',
        'payload',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'uptime_seconds' => 'integer',
            'download_bytes' => 'integer',
            'upload_bytes' => 'integer',
            'payload' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class, 'mikrotik_router_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
