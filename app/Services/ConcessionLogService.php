<?php

namespace App\Services;

use App\Models\ConcessionLog;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Writes one row per privileged party concession and works out how much money
 * the concession is worth: prorated free days x (monthly package price / days
 * in that month), matching the proration PaymentService already uses.
 */
class ConcessionLogService
{
    public function recordGracePeriod(Customer $customer, ?Subscription $subscription, int $graceDays, ?string $reason): ConcessionLog
    {
        $graceDays = max(0, $graceDays);
        $anchor = Carbon::today();
        $rate = $this->rateFor($subscription, $anchor);

        return $this->write($customer, $subscription, [
            'action_type' => 'grace_period',
            'reason' => $reason,
            'free_days' => $graceDays,
            'new_valid_until' => $anchor->copy()->addDays($graceDays)->toDateString(),
            'estimated_value' => $this->money($graceDays * $rate['daily']),
        ], $rate);
    }

    /**
     * When a later payment deducts the consumed grace days from the fresh paid
     * month the party has effectively paid the grace back, so credit that value
     * to the admin who granted it with a matching negative "grace_recovered"
     * row. Returns null when there is no un-recovered grace on record.
     */
    public function recordGraceRecovered(Customer $customer, int $recoveredDays, ?CarbonInterface $paidAt = null): ?ConcessionLog
    {
        $recoveredDays = max(0, $recoveredDays);
        if ($recoveredDays === 0) {
            return null;
        }

        $grace = ConcessionLog::query()
            ->where('customer_id', $customer->id)
            ->where('action_type', 'grace_period')
            ->whereNull('meta->recovered_by_log_id')
            ->latest('id')
            ->first();

        if (! $grace) {
            return null;
        }

        $creditedDays = min($recoveredDays, (int) $grace->free_days);
        if ($creditedDays <= 0) {
            return null;
        }

        $paidAt = $paidAt ? Carbon::parse($paidAt) : Carbon::now();
        $daily = (float) $grace->daily_rate;

        $recovery = ConcessionLog::create([
            'customer_id' => $customer->id,
            'subscription_id' => $grace->subscription_id,
            'internet_package_id' => $grace->internet_package_id,
            'user_id' => $grace->user_id,
            'user_name' => $grace->user_name,
            'action_type' => 'grace_recovered',
            'reason' => 'Grace days repaid on recharge.',
            'free_days' => -$creditedDays,
            'package_monthly_price' => $grace->package_monthly_price,
            'daily_rate' => $grace->daily_rate,
            'estimated_value' => $this->money(-1 * $creditedDays * $daily),
            'value_status' => 'final',
            'closed_at' => $paidAt,
            'meta' => ['recovered_grace_log_id' => $grace->id],
        ]);

        $grace->forceFill([
            'meta' => array_merge($grace->meta ?? [], [
                'recovered_by_log_id' => $recovery->id,
                'recovered_days' => $creditedDays,
                'recovered_at' => $paidAt->toDateTimeString(),
            ]),
        ])->save();

        return $recovery;
    }

    /**
     * @param  'validity_override'|'quick_activate'  $actionType
     */
    public function recordValidityChange(
        Customer $customer,
        ?Subscription $subscription,
        string $actionType,
        ?CarbonInterface $previousValidUntil,
        CarbonInterface $newValidUntil,
        ?string $reason,
    ): ConcessionLog {
        $today = Carbon::today();
        $newUntil = Carbon::parse($newValidUntil)->startOfDay();

        // Free days start from whichever is later: today, or the validity the
        // party had already paid for. Shortening validity yields zero.
        $baseline = $previousValidUntil
            ? Carbon::parse($previousValidUntil)->startOfDay()->max($today)
            : $today->copy();

        $freeDays = (int) max(0, $baseline->diffInDays($newUntil, false));
        $rate = $this->rateFor($subscription, $baseline);

        return $this->write($customer, $subscription, [
            'action_type' => $actionType,
            'reason' => $reason,
            'free_days' => $freeDays,
            'previous_valid_until' => $previousValidUntil ? Carbon::parse($previousValidUntil)->toDateString() : null,
            'new_valid_until' => $newUntil->toDateString(),
            'estimated_value' => $this->money($freeDays * $rate['daily']),
        ], $rate);
    }

    /**
     * Force-active has no end date yet, so the row is opened as "pending" with
     * zero value and settled later by closeOpenForceActive().
     */
    public function recordForceActive(Customer $customer, ?Subscription $subscription, ?string $reason): ConcessionLog
    {
        $rate = $this->rateFor($subscription, Carbon::today());

        return $this->write($customer, $subscription, [
            'action_type' => 'force_active',
            'reason' => $reason,
            'estimated_value' => 0,
            'value_status' => 'pending',
        ], $rate);
    }

    public function recordForceInactive(Customer $customer, ?Subscription $subscription, ?string $reason): ConcessionLog
    {
        $this->closeOpenForceActive($customer, Carbon::now(), 'force_inactive');

        $rate = $this->rateFor($subscription, Carbon::today());

        return $this->write($customer, $subscription, [
            'action_type' => 'force_inactive',
            'reason' => $reason,
            'estimated_value' => 0,
        ], $rate);
    }

    public function recordSpecialToggle(Customer $customer, bool $isSpecialNow, ?string $reason = null): ConcessionLog
    {
        $subscription = $this->resolveSubscription($customer);
        $rate = $this->rateFor($subscription, Carbon::today());

        if (! $isSpecialNow) {
            // Turning the flag off settles the give-away that has been running
            // for as long as the party was marked special.
            $this->closeOpenSpecial($customer, Carbon::now());
        }

        return $this->write($customer, $subscription, [
            'action_type' => $isSpecialNow ? 'mark_special' : 'unmark_special',
            'reason' => $reason,
            'estimated_value' => 0,
            'value_status' => $isSpecialNow ? 'pending' : 'final',
        ], $rate);
    }

    /**
     * Settle the most recent open "marked special" period for a party. The row
     * stays open (closed_at null) while the never-suspend flag is on, so its
     * value keeps growing until this is called.
     */
    public function closeOpenSpecial(Customer $customer, CarbonInterface $closedAt): ?ConcessionLog
    {
        $open = ConcessionLog::query()
            ->where('customer_id', $customer->id)
            ->where('action_type', 'mark_special')
            ->whereNull('closed_at')
            ->latest('id')
            ->first();

        if (! $open) {
            return null;
        }

        $closedAt = Carbon::parse($closedAt);
        $days = $this->inclusiveDays($open->created_at, $closedAt);
        $daily = (float) ($open->daily_rate ?: $this->rateFor($this->resolveSubscription($customer), $open->created_at)['daily']);

        $open->forceFill([
            'free_days' => $days,
            'estimated_value' => $this->money($days * $daily),
            'value_status' => 'final',
            'closed_at' => $closedAt,
            'meta' => array_merge($open->meta ?? [], ['closed_via' => 'unmark_special']),
        ])->save();

        return $open;
    }

    /**
     * Settle the most recent open force-active period for a party. Called when
     * the service is forced inactive, when validity is set again, or when a
     * payment brings the party current.
     */
    public function closeOpenForceActive(Customer $customer, CarbonInterface $closedAt, string $via): ?ConcessionLog
    {
        $open = ConcessionLog::query()
            ->where('customer_id', $customer->id)
            ->where('action_type', 'force_active')
            ->where('value_status', 'pending')
            ->latest('id')
            ->first();

        if (! $open) {
            return null;
        }

        $closedAt = Carbon::parse($closedAt);
        // Half-open interval: the day forced active counts, the day it is settled
        // is covered by the payment, so it does not.
        $days = (int) max(0, $open->created_at->copy()->startOfDay()->diffInDays($closedAt->copy()->startOfDay()));

        $daily = (float) ($open->daily_rate ?: $this->rateFor($this->resolveSubscription($customer), $open->created_at)['daily']);

        $open->forceFill([
            'free_days' => $days,
            'estimated_value' => $this->money($days * $daily),
            'value_status' => 'final',
            'closed_at' => $closedAt,
            'meta' => array_merge($open->meta ?? [], ['closed_via' => $via]),
        ])->save();

        return $open;
    }

    public function resolveSubscription(Customer $customer): ?Subscription
    {
        $subscription = $customer->relationLoaded('activeSubscription') && $customer->activeSubscription
            ? $customer->activeSubscription
            : $customer->subscriptions()->latest('id')->first();

        $subscription?->loadMissing('package');

        return $subscription;
    }

    /**
     * @return array{package: ?InternetPackage, monthly: float, daily: float, month_days: int}
     */
    private function rateFor(?Subscription $subscription, CarbonInterface $anchor): array
    {
        $package = $subscription?->package;
        $monthly = (float) ($package->monthly_price ?? 0);

        $start = Carbon::parse($anchor)->startOfDay();
        $monthDays = (int) max(1, $start->diffInDays($start->copy()->addMonthNoOverflow()));

        return [
            'package' => $package,
            'monthly' => $monthly,
            'daily' => $monthDays > 0 ? $monthly / $monthDays : 0.0,
            'month_days' => $monthDays,
        ];
    }

    /**
     * @param  array{package: ?InternetPackage, monthly: float, daily: float, month_days: int}  $rate
     */
    private function write(Customer $customer, ?Subscription $subscription, array $attributes, array $rate): ConcessionLog
    {
        $user = auth()->user();

        return ConcessionLog::create(array_merge([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription?->id,
            'internet_package_id' => $rate['package']?->id,
            'user_id' => $user?->getKey(),
            'user_name' => $user?->name ?? 'System',
            'package_monthly_price' => $rate['monthly'],
            'daily_rate' => round($rate['daily'], 4),
            'value_status' => 'final',
            'meta' => ['month_days' => $rate['month_days']],
        ], $attributes));
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }

    /**
     * Whole days from start to end, counting the day the concession started as
     * the first day. Same-day start and end therefore counts as one day.
     */
    private function inclusiveDays(CarbonInterface $start, CarbonInterface $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lessThan($start)) {
            return 0;
        }

        return (int) $start->diffInDays($end) + 1;
    }
}
