<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'invoice_id',
        'product_id',
        'product_name',
        'product_type',
        'quantity',
        'unit_price',
        'total',
        'serial_numbers',
        'warranty_days',
        'service_guarantee_days',
        'service_guarantee_until',
        'service_note',
    ];

    protected function casts(): array
    {
        return [
            'service_guarantee_until' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
