<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMaintenancePhoto extends Model
{
    protected $fillable = ['vehicle_maintenance_log_id', 'path', 'original_name', 'mime_type', 'size'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function maintenanceLog(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenanceLog::class, 'vehicle_maintenance_log_id');
    }
}
