<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikImportedProfile extends Model
{
    protected $fillable = ['mikrotik_router_id', 'routeros_id', 'name', 'local_address', 'remote_address', 'rate_limit', 'disabled', 'source_note', 'imported_at'];

    protected function casts(): array
    {
        return ['disabled' => 'boolean', 'imported_at' => 'datetime'];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class, 'mikrotik_router_id');
    }
}
