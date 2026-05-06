<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use RuntimeException;

class MikrotikCustomerSyncService
{
    public const DEFAULT_PASSWORD = '4321';

    public function sync(Customer $customer): string
    {
        $customer->loadMissing('activeSubscription.package');

        $routers = $customer->mikrotik_router_id
            ? MikrotikRouter::whereKey($customer->mikrotik_router_id)->where('status', 'active')->get()
            : MikrotikRouter::where('status', 'active')->orderBy('id')->get();

        if ($routers->isEmpty()) {
            throw new RuntimeException('No active MikroTik router configured.');
        }

        $results = [];

        foreach ($routers as $router) {
            $client = new RouterOsClient();

            try {
                $client->connect($router->ip_address, $router->api_port, $router->username, $router->password);

                $results[] = "{$router->name}: ".$this->syncPppSecret($client, $customer);
            } finally {
                $client->close();
            }
        }

        return implode(', ', $results);
    }

    private function syncPppSecret(RouterOsClient $client, Customer $customer): string
    {
        $username = $customer->mikrotik_username ?: $customer->connection_id;
        $password = $customer->mikrotik_password ?: self::DEFAULT_PASSWORD;
        $subscription = $customer->activeSubscription;
        $package = $subscription?->package;
        $disabled = $customer->status !== 'active' || ! $subscription || $subscription->status !== 'active' || ! $package;
        $profile = $package?->mikrotik_profile ?: $package?->name;

        $existing = $client->command('/ppp/secret/print', [
            '?name' => $username,
            '.proplist' => '.id',
        ]);

        if ($disabled) {
            if ($existing !== []) {
                $client->command('/ppp/secret/set', [
                    '.id' => $existing[0]['.id'],
                    'disabled' => 'yes',
                    'comment' => $customer->name,
                ]);

                return 'disabled';
            }

            return 'skipped';
        }

        $payload = [
            'name' => $username,
            'password' => $password,
            'profile' => $profile,
            'service' => 'pppoe',
            'comment' => $customer->name,
            'disabled' => 'no',
        ];

        $this->ensurePppProfile($client, $profile);

        if ($existing === []) {
            $client->command('/ppp/secret/add', $payload);

            return 'created';
        }

        unset($payload['name']);

        $client->command('/ppp/secret/set', [
            '.id' => $existing[0]['.id'],
            ...$payload,
        ]);

        return 'updated';
    }

    private function ensurePppProfile(RouterOsClient $client, string $profile): void
    {
        $existing = $client->command('/ppp/profile/print', [
            '?name' => $profile,
            '.proplist' => '.id',
        ]);

        if ($existing !== []) {
            return;
        }

        $client->command('/ppp/profile/add', [
            'name' => $profile,
        ]);
    }
}
