<?php

namespace App\Observers;

use App\Models\Subscription;

class SubscriptionIpObserver
{
    public function created(Subscription $subscription): void
    {
        $this->resetDynamicIp($subscription);
    }

    public function updated(Subscription $subscription): void
    {
        if ($subscription->wasChanged('internet_package_id')) {
            $this->resetDynamicIp($subscription);
        }
    }

    private function resetDynamicIp(Subscription $subscription): void
    {
        $customer = $subscription->customer()->first();

        if (! $customer || $customer->use_fixed_ip) {
            return;
        }

        $customer->forceFill([
            'learned_ip_address' => null,
            'learned_ip_package_id' => null,
            'last_connected_ip' => null,
            'last_connected_at' => null,
        ])->saveQuietly();
    }
}
