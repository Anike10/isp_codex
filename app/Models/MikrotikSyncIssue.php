<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MikrotikSyncIssue extends Model
{
    protected $fillable = [
        'issue_key',
        'mikrotik_router_id',
        'customer_id',
        'username',
        'issue_type',
        'expected_profile',
        'actual_profile',
        'details',
        'last_error',
        'attempt_count',
        'first_detected_at',
        'last_detected_at',
        'last_attempted_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function failures(): HasMany
    {
        return $this->hasMany(MikrotikSyncFailure::class);
    }
}
