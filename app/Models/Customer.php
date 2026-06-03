<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'name',
        'phone',
        'email',
        'connection_id',
        'mikrotik_username',
        'mikrotik_password',
        'mikrotik_router_id',
        'address',
        'status',
        'never_suspend',
        'grace_until',
        'grace_days',
        'grace_used_at',
        'account_balance',
        'is_customer',
        'is_vendor',
    ];

    protected $hidden = [
        'mikrotik_password',
    ];

    protected function casts(): array
    {
        return [
            'mikrotik_password' => 'encrypted',
            'never_suspend' => 'boolean',
            'grace_until' => 'date',
            'grace_used_at' => 'datetime',
            'account_balance' => 'decimal:2',
            'is_customer' => 'boolean',
            'is_vendor' => 'boolean',
        ];
    }

    public function hasActiveGracePeriod(): bool
    {
        return $this->grace_until && $this->grace_until->copy()->endOfDay()->gte(now());
    }

    public function activeUntil(): ?Carbon
    {
        if ($this->hasActiveGracePeriod()) {
            return $this->grace_until->copy()->endOfDay();
        }

        $earliestUnpaidMonth = $this->earliest_unpaid_billing_month
            ?? $this->invoices()
                ->where('invoice_type', 'service')
                ->where('due_amount', '>', 0)
                ->min('billing_month');

        if ($earliestUnpaidMonth) {
            return Carbon::createFromFormat('Y-m', $earliestUnpaidMonth)->subMonthNoOverflow()->endOfMonth();
        }

        $billingMonth = $this->latest_paid_billing_month
            ?? $this->invoices()
                ->where('invoice_type', 'service')
                ->where('due_amount', '<=', 0)
                ->max('billing_month');

        if (! $billingMonth) {
            return null;
        }

        return Carbon::createFromFormat('Y-m', $billingMonth)->endOfMonth();
    }

    public function activeDaysRemaining(): ?int
    {
        $activeUntil = $this->activeUntil();

        if (! $activeUntil) {
            return null;
        }

        return (int) max(0, now()->startOfDay()->diffInDays($activeUntil->copy()->startOfDay(), false));
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function mikrotikRouter(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(CustomerBalanceTransaction::class);
    }

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class, 'party_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function productSerials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }
}
