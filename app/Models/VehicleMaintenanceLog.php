<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMaintenanceLog extends Model
{
    public const ACTIONS = ['checked' => 'Checked', 'changed' => 'Changed / Replaced', 'serviced' => 'Serviced', 'repaired' => 'Repaired'];

    protected $fillable = ['vehicle_id', 'maintenance_item_id', 'action', 'service_date', 'mileage', 'cost', 'vendor', 'details', 'created_by'];

    protected function casts(): array
    {
        return ['service_date' => 'date', 'mileage' => 'integer', 'cost' => 'decimal:2'];
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
}
