<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entry_by',
        'name',
        'phone',
        'email',
        'connection_id',
        'mikrotik_username',
        'mikrotik_password',
        'mikrotik_router_id',
        'use_fixed_ip',
        'fixed_ip_address',
        'learned_ip_address',
        'learned_ip_package_id',
        'last_connected_ip',
        'last_connected_mac',
        'last_connected_at',
        'address',
        'map_latitude',
        'map_longitude',
        'notes',
        'status',
        'never_suspend',
        'grace_until',
        'grace_days',
        'grace_used_at',
        'service_valid_from',
        'service_valid_until',
        'service_validity_note',
        'account_balance',
        'is_customer',
        'is_vendor',
        'is_reseller',
        'reseller_id',
        'reseller_daily_payment_limit',
        'reseller_commission_percent',
    ];

    protected $hidden = [
        'mikrotik_password',
    ];

    protected function casts(): array
    {
        return [
            'mikrotik_password' => 'encrypted',
            'use_fixed_ip' => 'boolean',
            'last_connected_at' => 'datetime',
            'map_latitude' => 'decimal:8',
            'map_longitude' => 'decimal:8',
            'never_suspend' => 'boolean',
            'grace_until' => 'date',
            'grace_used_at' => 'datetime',
            'service_valid_from' => 'date',
            'service_valid_until' => 'date',
            'account_balance' => 'decimal:2',
            'is_customer' => 'boolean',
            'is_vendor' => 'boolean',
            'is_reseller' => 'boolean',
            'reseller_daily_payment_limit' => 'decimal:2',
            'reseller_commission_percent' => 'decimal:2',
        ];
    }

    public function hasActiveGracePeriod(): bool
    {
        return $this->grace_until && $this->grace_until->copy()->endOfDay()->gte(now());
    }

    public function activeUntil(): ?Carbon
    {
        if ($this->service_valid_until) {
            return $this->service_valid_until->copy()->endOfDay();
        }

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

        return (int) now()->startOfDay()->diffInDays($activeUntil->copy()->startOfDay(), false);
    }

    public function displayStatus(): string
    {
        if ($this->status === 'inactive') {
            return 'Inactive';
        }

        if ($this->status === 'active') {
            return 'Active';
        }

        return ucfirst((string) $this->status ?: 'Unknown');
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->displayStatus();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function mikrotikRouter(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class);
    }

    public function mikrotikRouters(): BelongsToMany
    {
        return $this->belongsToMany(MikrotikRouter::class)->withTimestamps();
    }

    public function scopeAssignedToMikrotikRouter(Builder $query, int $routerId): Builder
    {
        return $query->where(function (Builder $query) use ($routerId): void {
            $query->whereHas('mikrotikRouters', fn (Builder $query) => $query->whereKey($routerId))
                ->orWhere(function (Builder $query) use ($routerId): void {
                    $query->whereDoesntHave('mikrotikRouters')
                        ->where(function (Builder $query) use ($routerId): void {
                            $query->whereNull('mikrotik_router_id')
                                ->orWhere('mikrotik_router_id', $routerId);
                        });
                });
        });
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
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

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reseller_id');
    }

    public function resellerCustomers(): HasMany
    {
        return $this->hasMany(self::class, 'reseller_id');
    }

    public function loginUsers(): HasMany
    {
        return $this->hasMany(User::class, 'reseller_id');
    }

    public function fundedPaymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'funded_by_customer_id');
    }

    public function commissionHistories(): HasMany
    {
        return $this->hasMany(ResellerCommissionHistory::class, 'reseller_id')->latest('changed_at');
    }

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class, 'party_id');
    }

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
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

    public function versions(): MorphMany
    {
        return $this->morphMany(RecordVersion::class, 'versionable')->latest('id');
    }

    public function importedSecret(): HasOne
    {
        return $this->hasOne(MikrotikImportedSecret::class);
    }
}
