<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VehicleMaintenanceLog extends Model
{
    public const ACTIONS = ['checked' => 'Checked', 'changed' => 'Changed / Replaced', 'serviced' => 'Serviced', 'repaired' => 'Repaired'];

    protected $fillable = ['vehicle_id', 'maintenance_item_id', 'work_name', 'action', 'service_date', 'mileage', 'cost', 'vendor', 'details', 'youtube_url', 'finalized_at', 'finalized_by', 'created_by'];

    protected function casts(): array
    {
        return ['service_date' => 'date', 'mileage' => 'integer', 'cost' => 'decimal:2', 'finalized_at' => 'datetime'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenanceItem::class, 'maintenance_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(VehicleMaintenancePhoto::class);
    }

    public function versions(): MorphMany
    {
        return $this->morphMany(RecordVersion::class, 'versionable')->latest('id');
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }
}
