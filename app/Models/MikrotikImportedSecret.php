<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikImportedSecret extends Model
{
    protected $fillable = ['mikrotik_router_id', 'customer_id', 'routeros_id', 'name', 'password', 'service', 'profile', 'local_address', 'remote_address', 'device_mac', 'disabled', 'router_comment', 'notes', 'imported_at'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'disabled' => 'boolean', 'imported_at' => 'datetime'];
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
