<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_bill_id',
        'purchase_bill_item_id',
        'customer_id',
        'invoice_id',
        'invoice_item_id',
        'serial_number',
        'warranty_until',
        'sold_at',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'warranty_until' => 'date',
            'sold_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }
}
