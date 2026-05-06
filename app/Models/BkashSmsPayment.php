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
