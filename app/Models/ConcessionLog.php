<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ConcessionLog extends Model
{
    public const ACTION_LABELS = [
        'grace_period' => 'Grace period',
        'validity_override' => 'Validity date override',
        'quick_activate' => 'Quick-activate to next date',
        'force_active' => 'Force service active',
        'force_inactive' => 'Force service inactive',
        'mark_special' => 'Marked special (never suspend)',
        'unmark_special' => 'Removed special flag',
    ];

    protected $fillable = [
        'customer_id',
        'subscription_id',
        'internet_package_id',
        'user_id',
        'user_name',
        'action_type',
        'reason',
        'free_days',
        'previous_valid_until',
        'new_valid_until',
        'package_monthly_price',
        'daily_rate',
        'estimated_value',
        'value_status',
        'closed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'previous_valid_until' => 'date',
            'new_valid_until' => 'date',
            'package_monthly_price' => 'decimal:2',
            'daily_rate' => 'decimal:4',
            'estimated_value' => 'decimal:2',
            'closed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'internet_package_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action_type] ?? ucfirst(str_replace('_', ' ', (string) $this->action_type));
    }

    /**
     * A concession whose money value is still growing: force-active periods that
     * have not been settled, and special (never-suspend) flags that are still on.
     */
    public function isRunning(): bool
    {
        return $this->value_status === 'pending'
            || ($this->action_type === 'mark_special' && $this->closed_at === null);
    }

    /**
     * Days of give-away to attribute to this concession right now. A "marked
     * special" period counts both the day it started and today, since the party
     * is special for the whole of each; other running periods use a half-open
     * interval so a later payment can cover the closing day.
     */
    public function displayFreeDays(?CarbonInterface $asOf = null): ?int
    {
        if (! $this->isRunning()) {
            return $this->free_days;
        }

        $end = $this->closed_at
            ? Carbon::parse($this->closed_at)
            : ($asOf ? Carbon::parse($asOf) : Carbon::now());

        $startDay = ($this->created_at ?? $end)->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        if ($endDay->lessThan($startDay)) {
            return 0;
        }

        $days = (int) $startDay->diffInDays($endDay);
        if ($this->action_type === 'mark_special') {
            $days += 1;
        }

        return $days;
    }

    /**
     * Money value to show right now: the stored figure for settled rows, or the
     * give-away accrued so far for a still-running concession.
     */
    public function displayValue(?CarbonInterface $asOf = null): float
    {
        if (! $this->isRunning()) {
            return (float) $this->estimated_value;
        }

        return round((int) $this->displayFreeDays($asOf) * (float) $this->daily_rate, 2);
    }

    public function scopeAction(Builder $query, ?string $actionType): Builder
    {
        return $actionType ? $query->where('action_type', $actionType) : $query;
    }

    public function scopeByUser(Builder $query, int|string|null $userId): Builder
    {
        return $userId ? $query->where('user_id', $userId) : $query;
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to));
    }
}
