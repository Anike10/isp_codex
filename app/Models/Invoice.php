<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

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
        'discount_type',
        'discount_value',
        'vat',
        'vat_type',
        'vat_value',
        'total',
        'paid_amount',
        'due_amount',
        'status',
        'finalized_at',
        'due_date',
        'payment_note',
        'public_note',
        'show_public_note',
        'private_note',
    ];

    public static function generateInvoiceNo(int $customerId, string $billingMonth): string
    {
        $customerKey = str_pad((string) $customerId, 5, '0', STR_PAD_LEFT);
        $base = 'INV-'.$billingMonth.'-'.$customerKey;
        $count = self::where('customer_id', $customerId)
            ->where('billing_month', $billingMonth)
            ->count();

        do {
            $invoiceNo = $count === 0 ? $base : $base.'-'.str_pad((string) ($count + 1), 2, '0', STR_PAD_LEFT);
            $count++;
        } while (self::where('invoice_no', $invoiceNo)->exists());

        return $invoiceNo;
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'finalized_at' => 'datetime',
            'show_public_note' => 'boolean',
        ];
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
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

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function saleReturnCreditTotal(): float
    {
        return round((float) $this->saleReturns()->sum('credit_total'), 2);
    }

    public function recalculateSettlement(): void
    {
        $returnCredit = $this->saleReturnCreditTotal();
        $due = round(max(0, (float) $this->total - (float) $this->paid_amount - $returnCredit), 2);

        $this->due_amount = $due;
        $this->status = $due > 0
            ? (((float) $this->paid_amount > 0 || $returnCredit > 0) ? 'partial' : 'unpaid')
            : (($returnCredit >= (float) $this->total && (float) $this->paid_amount <= 0) ? 'returned' : 'paid');
        $this->save();
    }

    public function versions(): MorphMany
    {
        return $this->morphMany(RecordVersion::class, 'versionable')->latest('id');
    }
}
