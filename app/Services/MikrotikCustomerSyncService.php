<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use RuntimeException;
use Throwable;

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
        $failures = [];

        foreach ($routers as $router) {
            $client = new RouterOsClient();
            $routerLabel = "{$router->name} ({$router->ip_address}:{$router->api_port})";

            try {
                $client->connect($router->ip_address, $router->api_port, $router->username, $router->password);

                $results[] = "{$routerLabel}: ".$this->syncPppSecret($client, $customer, $router);
            } catch (Throwable $exception) {
                $failures[] = "{$routerLabel}: failed - ".$exception->getMessage();
            } finally {
                $client->close();
            }
        }

        if ($results === []) {
            throw new RuntimeException('All MikroTik sync attempts failed. '.implode(' | ', $failures));
        }

        return implode(', ', [...$results, ...$failures]);
    }

    public function syncRouter(MikrotikRouter $router): array
    {
        $client = new RouterOsClient();
        $summary = [
            'created' => 0,
            'updated' => 0,
            'moved_inactive' => 0,
            'skipped' => 0,
            'failed' => 0,
            'messages' => [],
        ];

        try {
            $client->connect($router->ip_address, $router->api_port, $router->username, $router->password);

            Customer::query()
                ->with('activeSubscription.package')
                ->where(function ($query) use ($router) {
                    $query->whereNull('mikrotik_router_id')
                        ->orWhere('mikrotik_router_id', $router->id);
                })
                ->orderBy('id')
                ->chunkById(100, function ($customers) use ($client, $router, &$summary): void {
                    foreach ($customers as $customer) {
                        try {
                            $status = $this->syncPppSecret($client, $customer, $router);

                            if ($status === 'created') {
                                $summary['created']++;
                            } elseif ($status === 'moved_inactive') {
                                $summary['moved_inactive']++;
                            } elseif ($status === 'skipped') {
                                $summary['skipped']++;
                            } else {
                                $summary['updated']++;
                            }
                        } catch (Throwable $exception) {
                            $summary['failed']++;
                            $summary['messages'][] = "{$customer->connection_id}: ".$exception->getMessage();
                        }
                    }
                });
        } finally {
            $client->close();
        }

        return $summary;
    }

    private function syncPppSecret(RouterOsClient $client, Customer $customer, MikrotikRouter $router): string
    {
        $username = $customer->mikrotik_username ?: $customer->connection_id;
        $password = $customer->mikrotik_password ?: self::DEFAULT_PASSWORD;
        $subscription = $customer->activeSubscription;
        $package = $subscription?->package;
        $inactive = $customer->status !== 'active' || ! $subscription || $subscription->status !== 'active' || ! $package;
        $profile = $inactive ? $router->inactive_pppoe_profile : ($package?->mikrotik_profile ?: $package?->name);

        $existing = $client->command('/ppp/secret/print', [
            '?name' => $username,
            '.proplist' => '.id,profile,disabled',
        ]);

        $this->ensurePppProfile($client, $profile);

        $payload = [
            'name' => $username,
            'password' => $password,
            'profile' => $profile,
            'service' => 'pppoe',
            'comment' => $customer->name,
            'disabled' => 'no',
        ];

        $oldProfile = $existing[0]['profile'] ?? null;
        $oldDisabled = $existing[0]['disabled'] ?? null;

        if ($existing === []) {
            $client->command('/ppp/secret/add', $payload);
            $this->disconnectActiveSession($client, $username);

            return $inactive ? 'moved_inactive' : 'created';
        }

        unset($payload['name']);

        $client->command('/ppp/secret/set', [
            '.id' => $existing[0]['.id'],
            ...$payload,
        ]);

        if ($oldProfile !== $profile || $oldDisabled === 'true') {
            $this->disconnectActiveSession($client, $username);
        }

        return $inactive ? 'moved_inactive' : 'updated';
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

    private function disconnectActiveSession(RouterOsClient $client, string $username): void
    {
        $activeSessions = $client->command('/ppp/active/print', [
            '?name' => $username,
            '.proplist' => '.id',
        ]);

        foreach ($activeSessions as $session) {
            if (isset($session['.id'])) {
                $client->command('/ppp/active/remove', [
                    '.id' => $session['.id'],
                ]);
            }
        }
    }
}
