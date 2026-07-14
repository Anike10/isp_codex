<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    public const STATUSES = ['active' => 'Active', 'maintenance' => 'Maintenance', 'inactive' => 'Inactive'];

    public const FUEL_TYPES = ['diesel' => 'Diesel', 'octane' => 'Octane', 'cng' => 'CNG', 'electric' => 'Electric', 'other' => 'Other'];

    protected $fillable = ['registration_no', 'name', 'vehicle_type', 'make', 'model', 'year', 'chassis_no', 'engine_no', 'fuel_type', 'status', 'current_mileage', 'note'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'current_mileage' => 'integer'];
    }

    public function maintenanceItems(): HasMany
    {
        return $this->hasMany(VehicleMaintenanceItem::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(VehicleMaintenanceLog::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignmentHistory::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereNull('end_date');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(VehicleExpense::class);
    }
}
