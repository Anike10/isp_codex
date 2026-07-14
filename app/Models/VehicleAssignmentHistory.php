<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAssignmentHistory extends Model
{
    protected $table = 'vehicle_assignments_history';

    public const ROLES = ['driver' => 'Driver', 'helper' => 'Helper', 'supervisor' => 'Supervisor'];

    protected $fillable = ['vehicle_id', 'employee_id', 'duty_role', 'start_date', 'end_date', 'note', 'assigned_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
