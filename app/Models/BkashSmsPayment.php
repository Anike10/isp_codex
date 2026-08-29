<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BkashSmsPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'paid_by_name',
        'sms_sender',
        'raw_sms',
        'customer_number',
        'trx_id',
        'ledger_trx_id',
        'reference',
        'amount',
        'payment_date',
        'status',
        'customer_id',
        'invoice_id',
        'payment_id',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    /**
     * How the row reads in the Status column. A processed row is split
     * into "auto" (matched and posted by the SMS webhook) vs "manual"
     * (an admin approved it — paid_by_name is stamped). Every other
     * status shows as-is (pending, failed, balance, duplicate).
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'processed') {
            return $this->paid_by_name ? 'manual' : 'auto';
        }

        return (string) $this->status;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
