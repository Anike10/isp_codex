<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PppUsageLog extends Model
{
    protected $fillable = [
        'mikrotik_router_id',
        'customer_id',
        'olt_onu_id',
        'username',
        'caller_id',
        'reported_router_id',
        'uptime',
        'uptime_seconds',
        'download_bytes',
        'upload_bytes',
        'rx_power_dbm',
        'payload',
        'disconnected_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'uptime_seconds' => 'integer',
            'download_bytes' => 'integer',
            'upload_bytes' => 'integer',
            'rx_power_dbm' => 'decimal:2',
            'disconnected_at' => 'datetime',
        ];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class, 'mikrotik_router_id');
    }

    public function oltOnu(): BelongsTo
    {
        return $this->belongsTo(OltOnu::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
