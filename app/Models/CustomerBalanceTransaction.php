<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBalanceTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'customer_id',
        'payment_id',
        'invoice_id',
        'payment_account_id',
        'payment_method',
        'direction',
        'amount',
        'balance_after',
        'transaction_date',
        'reference',
        'operation_key',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    public function entryByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entry_by');
    }

    public function getEnteredByLabelAttribute(): string
    {
        if ($this->entry_by_type === 'user') {
            return $this->entryByUser?->name ?? 'User #'.$this->entry_by;
        }

        return filled($this->entry_by) ? (string) $this->entry_by : 'system';
    }
}
