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
        $customer->loadMissing(['activeSubscription.package', 'mikrotikRouters']);

        $routers = $customer->mikrotikRouters->isNotEmpty()
            ? $customer->mikrotikRouters->where('status', 'active')->sortBy('id')->values()
            : ($customer->mikrotik_router_id
                ? MikrotikRouter::whereKey($customer->mikrotik_router_id)->where('status', 'active')->get()
                : MikrotikRouter::where('status', 'active')->orderBy('id')->get());

        if ($routers->isEmpty()) {
            throw new RuntimeException('No active MikroTik router configured.');
        }

        $results = [];
        $failures = [];

        foreach ($routers as $router) {
            $client = new RouterOsClient;
            $routerLabel = "{$router->name} ({$router->ip_address}:{$router->api_port})";

            try {
                $client->connect($router->ip_address, $router->api_port, $router->username, $router->apiPassword());

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

    public function remove(Customer $customer): string
    {
        $username = $customer->mikrotik_username ?: $customer->connection_id;
        if (! $username) {
            return 'skipped (no connection ID)';
        }

        $customer->loadMissing('mikrotikRouters');
        $routers = $customer->mikrotikRouters->isNotEmpty()
            ? $customer->mikrotikRouters->where('status', 'active')->sortBy('id')->values()
            : ($customer->mikrotik_router_id
                ? MikrotikRouter::whereKey($customer->mikrotik_router_id)->where('status', 'active')->get()
                : MikrotikRouter::where('status', 'active')->orderBy('id')->get());

        if ($routers->isEmpty()) {
            throw new RuntimeException('No active MikroTik router configured.');
        }

        $results = [];
        $failures = [];

        foreach ($routers as $router) {
            $client = new RouterOsClient;
            $routerLabel = "{$router->name} ({$router->ip_address}:{$router->api_port})";

            try {
                $client->connect($router->ip_address, $router->api_port, $router->username, $router->apiPassword());
                $this->disconnectActiveSession($client, $username);

                $secrets = $client->command('/ppp/secret/print', [
                    '?name' => $username,
                    '.proplist' => '.id',
                ]);

                $removed = 0;
                foreach ($secrets as $secret) {
                    if (! isset($secret['.id'])) {
                        continue;
                    }

                    $client->command('/ppp/secret/remove', ['.id' => $secret['.id']]);
                    $removed++;
                }

                $results[] = "{$routerLabel}: {$removed} secret(s) removed";
            } catch (Throwable $exception) {
                $failures[] = "{$routerLabel}: ".$exception->getMessage();
            } finally {
                $client->close();
            }
        }

        if ($failures !== []) {
            throw new RuntimeException('MikroTik removal failed. '.implode(' | ', $failures));
        }

        return implode(', ', $results);
    }

    public function syncRouter(MikrotikRouter $router): array
    {
        $client = new RouterOsClient;
        $summary = [
            'created' => 0,
            'updated' => 0,
            'moved_inactive' => 0,
            'skipped' => 0,
            'failed' => 0,
            'active_sessions_captured' => 0,
            'messages' => [],
        ];

        try {
            $client->connect($router->ip_address, $router->api_port, $router->username, $router->apiPassword());
            $summary['active_sessions_captured'] = $this->captureActiveSessions($client, $router);
            $this->ensurePppProfile($client, $router->inactive_pppoe_profile);

            Customer::query()
                ->with('activeSubscription.package')
                ->assignedToMikrotikRouter($router->id)
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

    public function captureActiveSessions(RouterOsClient $client, MikrotikRouter $router): int
    {
        $sessions = collect($client->command('/ppp/active/print', [
            '.proplist' => '.id,name,address,caller-id,profile,service',
        ]))->filter(fn (array $session) => ! blank($session['name'] ?? null));

        if ($sessions->isEmpty()) {
            return 0;
        }

        $names = $sessions->pluck('name')->map(fn ($name) => trim((string) $name))->filter()->unique()->values();
        $customers = Customer::query()
            ->with('activeSubscription.package')
            ->assignedToMikrotikRouter($router->id)
            ->where(function ($query) use ($names): void {
                $query->whereIn('mikrotik_username', $names)
                    ->orWhereIn('connection_id', $names);
            })
            ->get();

        $customersByUsername = collect();
        foreach ($customers as $customer) {
            foreach (array_unique(array_filter([$customer->mikrotik_username, $customer->connection_id])) as $username) {
                $customersByUsername->put((string) $username, $customer);
            }
        }

        $captured = 0;
        foreach ($sessions as $session) {
            $username = trim((string) $session['name']);
            /** @var Customer|null $customer */
            $customer = $customersByUsername->get($username);
            if (! $customer) {
                continue;
            }

            $ipAddress = trim((string) ($session['address'] ?? '')) ?: null;
            $callerId = trim((string) ($session['caller-id'] ?? '')) ?: null;
            $package = $customer->activeSubscription?->package;
            $expectedProfile = $package?->mikrotik_profile ?: $package?->name;
            $sessionProfile = trim((string) ($session['profile'] ?? '')) ?: null;
            $isCurrentPackageSession = $package
                && (! $sessionProfile || $sessionProfile === $expectedProfile);

            $updates = ['last_connected_at' => now()];
            if ($ipAddress) {
                $updates['last_connected_ip'] = $ipAddress;
            }
            if ($callerId) {
                $updates['last_connected_mac'] = $this->normalizeMacAddress($callerId);
            }

            if (! $customer->use_fixed_ip && $isCurrentPackageSession && $ipAddress) {
                $updates['learned_ip_address'] = $ipAddress;
                $updates['learned_ip_package_id'] = $package->id;
            }

            $customer->forceFill($updates)->save();

            $remoteAddress = $customer->use_fixed_ip
                ? $customer->fixed_ip_address
                : ($isCurrentPackageSession ? $ipAddress : null);

            if ($remoteAddress) {
                $secret = $client->command('/ppp/secret/print', [
                    '?name' => $username,
                    '.proplist' => '.id,remote-address',
                ]);
                if ($secret !== [] && ($secret[0]['remote-address'] ?? null) !== $remoteAddress) {
                    $client->command('/ppp/secret/set', [
                        '.id' => $secret[0]['.id'],
                        'remote-address' => $remoteAddress,
                    ]);
                }
            }

            $captured++;
        }

        return $captured;
    }

    private function syncPppSecret(RouterOsClient $client, Customer $customer, MikrotikRouter $router): string
    {
        $username = $customer->mikrotik_username ?: $customer->connection_id;

        if (! $username) {
            return 'skipped';
        }

        $password = $customer->mikrotik_password ?: self::DEFAULT_PASSWORD;
        $subscription = $customer->activeSubscription;
        $package = $subscription?->package;
        $inactive = $customer->status !== 'active' || ! $subscription || $subscription->status !== 'active' || ! $package;
        $profile = $inactive ? $router->inactive_pppoe_profile : ($package?->mikrotik_profile ?: $package?->name);
        $remoteAddress = $this->remoteAddressFor($customer, $package?->id);

        $existing = $client->command('/ppp/secret/print', [
            '?name' => $username,
            '.proplist' => '.id,profile,disabled,remote-address',
        ]);

        $this->ensurePppProfile(
            $client,
            $profile,
            $inactive ? null : $package?->default_ip_pool,
            $inactive ? null : $this->rateLimitFromSpeed($package?->speed),
        );

        $payload = [
            'name' => $username,
            'password' => $password,
            'profile' => $profile,
            'service' => 'pppoe',
            'comment' => $customer->name,
            'disabled' => 'no',
        ];
        if ($remoteAddress) {
            $payload['remote-address'] = $remoteAddress;
        }

        $oldProfile = $existing[0]['profile'] ?? null;
        $oldDisabled = $existing[0]['disabled'] ?? null;
        $oldRemoteAddress = $existing[0]['remote-address'] ?? null;

        if ($existing === []) {
            $client->command('/ppp/secret/add', $payload);
            $this->disconnectActiveSession($client, $username);

            return $inactive ? 'moved_inactive' : 'created';
        }

        unset($payload['name']);
        if (! $remoteAddress && $oldRemoteAddress) {
            $payload['remote-address'] = '';
        }

        $client->command('/ppp/secret/set', [
            '.id' => $existing[0]['.id'],
            ...$payload,
        ]);

        if ($oldProfile !== $profile || $oldDisabled === 'true' || ($oldRemoteAddress ?: null) !== ($remoteAddress ?: null)) {
            $this->disconnectActiveSession($client, $username);
        }

        return $inactive ? 'moved_inactive' : 'updated';
    }

    public function ensurePppProfile(RouterOsClient $client, string $profile, ?string $defaultIpPool = null, ?string $rateLimit = null): void
    {
        $existing = $client->command('/ppp/profile/print', [
            '?name' => $profile,
            '.proplist' => '.id,remote-address,rate-limit',
        ]);

        if ($existing !== []) {
            $changes = [];
            if ($defaultIpPool && ($existing[0]['remote-address'] ?? null) !== $defaultIpPool) {
                $changes['remote-address'] = $defaultIpPool;
            }
            if ($rateLimit && ($existing[0]['rate-limit'] ?? null) !== $rateLimit) {
                $changes['rate-limit'] = $rateLimit;
            }
            if ($changes !== []) {
                $client->command('/ppp/profile/set', ['.id' => $existing[0]['.id'], ...$changes]);
            }

            return;
        }

        $attributes = [
            'name' => $profile,
        ];
        if ($defaultIpPool) {
            $attributes['remote-address'] = $defaultIpPool;
        }
        if ($rateLimit) {
            $attributes['rate-limit'] = $rateLimit;
        }
        $client->command('/ppp/profile/add', $attributes);
    }

    private function remoteAddressFor(Customer $customer, ?int $packageId): ?string
    {
        if ($customer->use_fixed_ip) {
            return $customer->fixed_ip_address ?: null;
        }

        if ($packageId && (int) $customer->learned_ip_package_id === $packageId) {
            return $customer->learned_ip_address ?: null;
        }

        return null;
    }

    private function rateLimitFromSpeed(?string $speed): ?string
    {
        $speed = trim((string) $speed);
        if ($speed === '') {
            return null;
        }
        if (str_contains($speed, '/')) {
            return $speed;
        }
        if (! preg_match('/(\d+(?:\.\d+)?)\s*(gbps|g|mbps|m|kbps|k)\b/i', $speed, $matches)) {
            return null;
        }

        $unit = strtolower($matches[2]);
        $suffix = str_starts_with($unit, 'g') ? 'G' : (str_starts_with($unit, 'k') ? 'k' : 'M');
        $value = rtrim(rtrim(number_format((float) $matches[1], 3, '.', ''), '0'), '.');
        $rate = $value.$suffix;

        return $rate.'/'.$rate;
    }

    private function normalizeMacAddress(?string $callerId): ?string
    {
        if (! $callerId) {
            return null;
        }

        return preg_match('/^[0-9a-f]{2}(?::[0-9a-f]{2}){5}$/i', $callerId)
            ? strtoupper($callerId)
            : $callerId;
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
