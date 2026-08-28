<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A hand-over of collected money from a payment account to the office. It debits
 * the account's live balance, which is how an owner brings the balance back
 * under its limit.
 */
class AccountDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'payment_account_id',
        'deposited_by',
        'amount',
        'deposited_at',
        'reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'deposited_at' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    public function depositedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deposited_by');
    }
}
