<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by', 'entry_by_type', 'customer_id', 'converted_invoice_id', 'quotation_no',
        'quotation_date', 'valid_until', 'billing_month', 'invoice_type', 'subtotal',
        'discount', 'discount_type', 'discount_value', 'vat', 'vat_type', 'vat_value',
        'total', 'status', 'payment_note', 'public_note', 'show_public_note', 'private_note',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'show_public_note' => 'boolean',
        ];
    }

    public static function generateQuotationNo(): string
    {
        $prefix = 'QT-'.now()->format('Ym').'-';
        $sequence = static::where('quotation_no', 'like', $prefix.'%')->count() + 1;

        do {
            $number = $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (static::where('quotation_no', $number)->exists());

        return $number;
    }

    public function getFormattedBillingMonthAttribute(): string
    {
        try {
            return Carbon::createFromFormat('!Y-m', $this->billing_month)->format('F Y');
        } catch (\Throwable) {
            return (string) $this->billing_month;
        }
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function versions(): MorphMany
    {
        return $this->morphMany(RecordVersion::class, 'versionable')->latest();
    }
}
