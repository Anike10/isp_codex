<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'owner_user_id',
        'payment_method',
        'account_name',
        'account_number',
        'opening_balance',
        'balance_limit',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'balance_limit' => 'decimal:2',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(CustomerBalanceTransaction::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(AccountDeposit::class);
    }

    /**
     * Money on hand in this account right now: opening balance plus everything
     * collected, minus expenses paid from it and minus hand-overs to the office.
     */
    public function liveBalance(): float
    {
        $collected = (float) $this->payments()->sum('amount')
            + (float) $this->balanceTransactions()
                ->where('direction', 'credit')
                ->whereNull('payment_id')
                ->sum('amount');

        $spent = (float) $this->expenses()->sum('amount');
        $deposited = (float) $this->deposits()->sum('amount');

        return round((float) $this->opening_balance + $collected - $spent - $deposited, 2);
    }

    /** How much more can still be collected before the limit; null when uncapped. */
    public function remainingCapacity(): ?float
    {
        if ($this->balance_limit === null) {
            return null;
        }

        return round(max(0.0, (float) $this->balance_limit - $this->liveBalance()), 2);
    }

    /** Whether collecting $amount now would push the live balance past the limit. */
    public function wouldExceedLimit(float $amount): bool
    {
        if ($this->balance_limit === null) {
            return false;
        }

        return round($this->liveBalance() + $amount, 2) > (float) $this->balance_limit + 0.001;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** Other users a super admin has granted transaction access to. */
    public function delegates(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Limit the query to the accounts a user is allowed to record money
     * through: a super admin may use every account; anyone else may use the
     * accounts they own plus any granted to them. Unowned, ungranted accounts
     * stay super-admin only.
     */
    public function scopeUsableBy(Builder $query, ?User $user): Builder
    {
        if ($user?->isSuperAdmin()) {
            return $query;
        }

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where($query->qualifyColumn('owner_user_id'), $user->getKey())
                ->orWhereHas('delegates', fn (Builder $q) => $q->whereKey($user->getKey()));
        });
    }
}
