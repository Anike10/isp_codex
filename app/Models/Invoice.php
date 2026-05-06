<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'customer_id',
        'invoice_no',
        'billing_month',
        'invoice_type',
        'subtotal',
        'discount',
        'vat',
        'total',
        'paid_amount',
        'due_amount',
        'status',
        'finalized_at',
        'due_date',
    ];

    public static function generateInvoiceNo(int $customerId, string $billingMonth): string
    {
        $customerKey = str_pad((string) $customerId, 5, '0', STR_PAD_LEFT);
        $base = 'INV-'.$billingMonth.'-'.$customerKey;
        $count = self::where('customer_id', $customerId)
            ->where('billing_month', $billingMonth)
            ->count();

        return $count === 0 ? $base : $base.'-'.str_pad((string) ($count + 1), 2, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
