<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAssetReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_asset_assignment_id',
        'warehouse_id',
        'received_by_user_id',
        'quantity',
        'serialless_quantity',
        'serial_numbers',
        'returned_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'serialless_quantity' => 'integer',
            'returned_at' => 'date',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeAssetAssignment::class, 'employee_asset_assignment_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }
}
