<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_bill_id',
        'purchase_bill_item_id',
        'serial_number',
        'warranty_until',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'warranty_until' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }

    public function purchaseBillItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseBillItem::class);
    }
}
