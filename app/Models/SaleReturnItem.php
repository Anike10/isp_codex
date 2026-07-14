<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_return_id',
        'invoice_item_id',
        'product_id',
        'product_name',
        'quantity',
        'serialless_quantity',
        'unit_price',
        'total',
        'serial_numbers',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'serialless_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
