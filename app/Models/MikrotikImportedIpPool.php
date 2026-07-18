<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikImportedIpPool extends Model
{
    protected $fillable = ['mikrotik_router_id', 'routeros_id', 'name', 'ranges', 'next_pool', 'source_note', 'imported_at'];

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class, 'mikrotik_router_id');
    }
}
