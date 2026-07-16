<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VehicleExpense extends Model
{
    public const CATEGORIES = ['fuel_diesel' => 'Fuel - Diesel', 'fuel_octane' => 'Fuel - Octane', 'fuel_cng' => 'Fuel - CNG', 'engine_oil' => 'Engine Oil', 'spare_parts' => 'Spare Parts', 'toll_bridge' => 'Toll / Bridge', 'miscellaneous' => 'Miscellaneous'];

    protected $fillable = ['vehicle_id', 'employee_id', 'created_by', 'category', 'expense_date', 'amount', 'quantity', 'unit', 'mileage', 'trip_reference', 'vendor', 'description', 'finalized_at', 'finalized_by'];

    protected function casts(): array
    {
        return ['expense_date' => 'date', 'amount' => 'decimal:2', 'quantity' => 'decimal:3', 'mileage' => 'integer', 'finalized_at' => 'datetime'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
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
