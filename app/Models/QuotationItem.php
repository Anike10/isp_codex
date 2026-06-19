<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by', 'entry_by_type', 'quotation_id', 'product_id', 'product_name',
        'product_type', 'quantity', 'unit_price', 'total', 'serial_numbers',
        'serialless_quantity', 'warranty_days', 'service_guarantee_days',
        'service_guarantee_until', 'service_note',
    ];

    protected function casts(): array
    {
        return [
            'serialless_quantity' => 'integer',
            'service_guarantee_until' => 'date',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
