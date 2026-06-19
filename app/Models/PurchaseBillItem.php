<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseBillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_bill_id',
        'product_id',
        'quantity',
        'serialless_quantity',
        'unit_price',
        'total',
        'warranty_months',
        'warranty_days',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'serialless_quantity' => 'integer',
        ];
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }
}
