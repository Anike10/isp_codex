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

        return $this->write($customer, $subscription, [
            'action_type' => $isSpecialNow ? 'mark_special' : 'unmark_special',
            'reason' => $reason,
            'estimated_value' => 0,
        ], $rate);
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
}
