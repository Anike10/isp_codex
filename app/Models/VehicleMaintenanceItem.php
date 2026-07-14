<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleMaintenanceItem extends Model
{
    public const TYPES = ['routine_check' => 'Routine Check', 'replacement' => 'Replacement / Service'];

    protected $fillable = ['vehicle_id', 'name', 'maintenance_type', 'interval_days', 'interval_mileage', 'last_checked_at', 'last_changed_at', 'last_service_mileage', 'next_due_date', 'next_due_mileage', 'is_active', 'note'];

    protected function casts(): array
    {
        return ['last_checked_at' => 'date', 'last_changed_at' => 'date', 'next_due_date' => 'date', 'last_service_mileage' => 'integer', 'next_due_mileage' => 'integer', 'is_active' => 'boolean'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(VehicleMaintenanceLog::class, 'maintenance_item_id');
    }

    public function isDue(?int $mileage = null): bool
    {
        return ($this->next_due_date && $this->next_due_date->lte(today()))
            || ($this->next_due_mileage !== null && ($mileage ?? $this->vehicle?->current_mileage ?? 0) >= $this->next_due_mileage);
    }
}
