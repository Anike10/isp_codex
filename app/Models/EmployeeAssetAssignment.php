<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAssetAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'product_id',
        'warehouse_id',
        'issued_by_user_id',
        'source_condition',
        'quantity',
        'unit_price',
        'total',
        'serialless_quantity',
        'serial_numbers',
        'assigned_at',
        'purpose',
        'note',
        'approval_document_path',
        'approval_document_name',
        'approval_document_mime',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'serialless_quantity' => 'integer',
            'assigned_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(EmployeeAssetReturn::class);
    }

    public function returnedQuantity(): int
    {
        return (int) ($this->returns_sum_quantity ?? $this->returns()->sum('quantity'));
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity - $this->returnedQuantity());
    }

    public function returnedValue(): float
    {
        return round($this->returnedQuantity() * (float) $this->unit_price, 2);
    }

    public function outstandingValue(): float
    {
        return round($this->outstandingQuantity() * (float) $this->unit_price, 2);
    }
}
