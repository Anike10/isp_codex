<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikSyncFailure extends Model
{
    protected $fillable = [
        'mikrotik_sync_issue_id',
        'mikrotik_router_id',
        'customer_id',
        'username',
        'context',
        'error_message',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(MikrotikSyncIssue::class, 'mikrotik_sync_issue_id');
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
