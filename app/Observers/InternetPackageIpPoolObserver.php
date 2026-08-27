<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\InternetPackage;

class InternetPackageIpPoolObserver
{
    public function updated(InternetPackage $package): void
    {
        if (! $package->wasChanged('default_ip_pool')) {
            return;
        }

        $customerIds = $package->subscriptions()
            ->where('status', 'active')
            ->distinct()
            ->pluck('customer_id');

        Customer::query()
            ->whereIn('id', $customerIds)
            ->where('use_fixed_ip', false)
            ->update([
                'learned_ip_address' => null,
                'learned_ip_package_id' => null,
                'last_connected_ip' => null,
                'last_connected_at' => null,
            ]);
    }
}
