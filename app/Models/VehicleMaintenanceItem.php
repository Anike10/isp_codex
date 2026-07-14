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
        return in_array($this->dueStatus($mileage), ['due', 'overdue'], true);
    }

    public function daysRemaining(): ?int
    {
        return $this->next_due_date ? (int) today()->diffInDays($this->next_due_date, false) : null;
    }

    public function mileageRemaining(?int $mileage = null): ?int
    {
        return $this->next_due_mileage !== null
            ? $this->next_due_mileage - ($mileage ?? $this->vehicle?->current_mileage ?? 0)
            : null;
    }

    public function dueStatus(?int $mileage = null): string
    {
        $days = $this->daysRemaining();
        $kilometres = $this->mileageRemaining($mileage);

        if (($days !== null && $days < 0) || ($kilometres !== null && $kilometres < 0)) {
            return 'overdue';
        }

        if (($days !== null && $days === 0) || ($kilometres !== null && $kilometres === 0)) {
            return 'due';
        }

        return $days === null && $kilometres === null ? 'unscheduled' : 'upcoming';
    }
}
