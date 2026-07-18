<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppIpPool extends Model
{
    protected $fillable = ['mikrotik_router_id', 'name', 'ranges', 'next_pool', 'notes', 'status'];

    public function router(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class, 'mikrotik_router_id');
    }
}
