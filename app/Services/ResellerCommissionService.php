<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;

class ResellerCommissionService
{
    public function calculate(Customer $customer, float $grossTotal, ?User $actor = null): array
    {
        $grossTotal = round(max(0, $grossTotal), 2);
        $actor ??= auth()->user();
        $reseller = $actor?->reseller;

        if (! $reseller
            || ! $reseller->is_reseller
            || $reseller->status !== 'active'
            || (int) $customer->reseller_id !== (int) $reseller->id) {
            return [
                'reseller_id' => null,
                'percent' => 0.0,
                'amount' => 0.0,
                'gross_total' => $grossTotal,
                'net_total' => $grossTotal,
            ];
        }

        $percent = round(min(100, max(0, (float) $reseller->reseller_commission_percent)), 2);
        $amount = round($grossTotal * $percent / 100, 2);

        return [
            'reseller_id' => $reseller->id,
            'percent' => $percent,
            'amount' => $amount,
            'gross_total' => $grossTotal,
            'net_total' => round(max(0, $grossTotal - $amount), 2),
        ];
    }
}
