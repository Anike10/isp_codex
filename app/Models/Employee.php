<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'designation',
        'fleet_role',
        'phone',
        'join_date',
        'current_salary',
        'salary_effective_from',
        'yearly_bonus_count',
        'bonus_percent',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'current_salary' => 'decimal:2',
            'salary_effective_from' => 'date',
            'bonus_percent' => 'decimal:2',
        ];
    }

    public function salaryRevisions(): HasMany
    {
        return $this->hasMany(EmployeeSalaryRevision::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function assetAssignments(): HasMany
    {
        return $this->hasMany(EmployeeAssetAssignment::class);
    }

    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(VehicleAssignmentHistory::class);
    }

    public function vehicleExpenses(): HasMany
    {
        return $this->hasMany(VehicleExpense::class);
    }
}
