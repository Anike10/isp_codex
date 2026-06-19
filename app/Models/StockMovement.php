<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'product_id',
        'warehouse_id',
        'related_warehouse_id',
        'type',
        'quantity',
        'serialless_quantity',
        'serial_numbers',
        'balance_before',
        'balance_after',
        'reason',
        'reference_no',
    ];

    protected function casts(): array
    {
        return [
            'serialless_quantity' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function relatedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'related_warehouse_id');
    }
}
