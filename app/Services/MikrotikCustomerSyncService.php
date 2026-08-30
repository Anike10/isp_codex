<?php

namespace App\Services;

use App\Models\AppIpPool;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use App\Observers\RecordVersionObserver;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class MikrotikCustomerSyncService
{
    public const DEFAULT_PASSWORD = '4321';

    private array $poolRangesCache = [];

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

        return $this->removeUsername($customer, $username);
    }

    /**
     * Remove the PPPoE secret for one explicit username from every router this
     * party is (or was) assigned to, closing any live session first.
     *
     * Separate from {@see remove()} because a rename has already overwritten
     * `mikrotik_username`; the caller passes the OLD name so the secret left
     * behind on the router can be cleaned up.
     */
    public function removeUsername(Customer $customer, string $username): string
    {
        $username = trim($username);
        if ($username === '') {
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
            $this->assertRouterHasUniqueSecretNames($client);
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

    /**
     * Poll `/ppp/active` and copy each session's device MAC (`caller-id`) onto
     * the party it belongs to (`last_connected_mac` in the party info), plus
     * the live IP / timestamp. Read-only, transport-agnostic, and far lighter
     * than {@see syncRouter()} so it can run on a short interval.
     *
     * `updated` counts only parties whose stored MAC actually changed this run;
     * `matched` is every session that resolved to a party (its "last seen" is
     * still refreshed even when the MAC is unchanged), and `no_mac` is sessions
     * whose `caller-id` was not a device MAC at all.
     *
     * @return array{sessions: int, no_mac: int, matched: int, unmatched: int, updated: int}
     */
    public function syncActiveConnectionMacs(MikrotikRouter $router): array
    {
        $records = app(MikrotikImportService::class)->liveRecords($router, '/ppp/active/print');

        $sessions = collect($records)
            ->filter(fn ($session) => ! blank($session['name'] ?? null));

        if ($sessions->isEmpty()) {
            return ['sessions' => 0, 'no_mac' => 0, 'matched' => 0, 'unmatched' => 0, 'updated' => 0];
        }

        $names = $sessions->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $byName = [];
        Customer::query()
            ->assignedToMikrotikRouter($router->id)
            ->where(function ($query) use ($names): void {
                $query->whereIn(DB::raw('lower(mikrotik_username)'), $names)
                    ->orWhereIn(DB::raw('lower(connection_id)'), $names);
            })
            ->get()
            ->each(function (Customer $customer) use (&$byName): void {
                foreach (array_filter([$customer->mikrotik_username, $customer->connection_id]) as $identifier) {
                    $byName[mb_strtolower(trim((string) $identifier))] = $customer;
                }
            });

        $updated = 0;
        $matched = 0;
        $unmatched = 0;
        $noMac = 0;

        foreach ($sessions as $session) {
            $mac = $this->normalizeMacAddress(trim((string) ($session['caller-id'] ?? '')) ?: null);
            // Only real MACs — some PPP services put an IP or interface name in
            // caller-id, and that must not land in last_connected_mac.
            if (! $mac || ! preg_match('/^[0-9a-f]{2}(?::[0-9a-f]{2}){5}$/i', $mac)) {
                $noMac++;

                continue;
            }

            $customer = $byName[mb_strtolower(trim((string) $session['name']))] ?? null;
            if (! $customer) {
                $unmatched++;

                continue;
            }

            $matched++;
            $ipAddress = trim((string) ($session['address'] ?? '')) ?: null;
            $updates = ['last_connected_at' => now()];
            if ($customer->last_connected_mac !== $mac) {
                $updates['last_connected_mac'] = $mac;
                $updated++;
            }
            if (! $customer->use_fixed_ip && $ipAddress !== null && $customer->last_connected_ip !== $ipAddress) {
                $updates['last_connected_ip'] = $ipAddress;
            }

            // "Last seen" telemetry changes on every poll — not worth an audit
            // row on every active party each run.
            RecordVersionObserver::withoutRecording(fn () => $customer->forceFill($updates)->save());
        }

        return [
            'sessions' => $sessions->count(),
            'no_mac' => $noMac,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'updated' => $updated,
        ];
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
            $ipMatchesPackagePool = $this->ipMatchesConfiguredPackagePool($package, $ipAddress, $router->id);

            $updates = [];
            if ($customer->use_fixed_ip || $isCurrentPackageSession) {
                $updates['last_connected_at'] = now();
                if ($ipAddress) {
                    $updates['last_connected_ip'] = $ipAddress;
                }
                if ($callerId) {
                    $updates['last_connected_mac'] = $this->normalizeMacAddress($callerId);
                }
            }

            if (! $customer->use_fixed_ip && $isCurrentPackageSession && $ipAddress && $ipMatchesPackagePool) {
                $updates['learned_ip_address'] = $ipAddress;
                $updates['learned_ip_package_id'] = $package->id;
            } elseif (! $customer->use_fixed_ip && $isCurrentPackageSession && $ipAddress) {
                $updates['learned_ip_address'] = null;
                $updates['learned_ip_package_id'] = null;
            }

            if ($updates !== []) {
                $customer->forceFill($updates)->save();
            }

            $remoteAddress = $customer->use_fixed_ip
                ? $customer->fixed_ip_address
                : ($isCurrentPackageSession && $ipMatchesPackagePool ? $ipAddress : null);

            if ($remoteAddress) {
                $secret = $client->command('/ppp/secret/print', [
                    '?name' => $username,
                    '.proplist' => '.id,name,remote-address',
                ]);
                $this->assertSingleExactSecret($secret, $username);

                if (($secret[0]['remote-address'] ?? null) !== $remoteAddress) {
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
        $subscription = $customer->activeSubscription
            ?: ($customer->never_suspend ? $customer->latestSubscription()->with('package')->first() : null);
        $package = $subscription?->package;

        if ($customer->never_suspend) {
            // Special ISP customers are never moved to the inactive profile;
            // as long as a package is known, keep them on the service profile.
            $inactive = ! $package;
        } else {
            $inactive = $customer->status !== 'active' || ! $subscription || $subscription->status !== 'active' || ! $package;
        }
        $profile = $inactive ? $router->inactive_pppoe_profile : ($package?->mikrotik_profile ?: $package?->name);
        $learnedIpMatchesPackagePool = $this->ipMatchesConfiguredPackagePool(
            $package,
            $customer->learned_ip_address,
            $router->id,
        );
        if (! $customer->use_fixed_ip && $customer->learned_ip_address && ! $learnedIpMatchesPackagePool) {
            $customer->forceFill([
                'learned_ip_address' => null,
                'learned_ip_package_id' => null,
            ])->saveQuietly();
        }
        $remoteAddress = $this->remoteAddressFor($customer, $package, $router->id);
        $activeSessionPoolMismatch = ! $customer->use_fixed_ip
            && ! $inactive
            && $customer->last_connected_ip
            && ! $this->ipMatchesConfiguredPackagePool($package, $customer->last_connected_ip, $router->id);

        if (! $inactive && strcasecmp(trim((string) $profile), trim((string) $router->inactive_pppoe_profile)) === 0) {
            throw new RuntimeException("Package {$package->name} uses the reserved inactive PPPoE profile.");
        }

        $existing = $client->command('/ppp/secret/print', [
            '?name' => $username,
            '.proplist' => '.id,name,password,profile,service,comment,disabled,remote-address',
        ]);

        if ($existing !== []) {
            $this->assertSingleExactSecret($existing, $username);
        }

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
        if (! $remoteAddress && $this->normalizeRemoteAddress($oldRemoteAddress)) {
            // RouterOS represents an unset PPP secret address as 0.0.0.0.
            // Sending an empty string through the API is rejected as an
            // invalid remote-address on affected RouterOS versions.
            $payload['remote-address'] = '0.0.0.0';
        }

        $changes = [];
        foreach ($payload as $attribute => $value) {
            if (! $this->secretAttributeMatches($attribute, $existing[0][$attribute] ?? null, $value)) {
                $changes[$attribute] = $value;
            }
        }

        if ($changes !== []) {
            $client->command('/ppp/secret/set', [
                '.id' => $existing[0]['.id'],
                ...$changes,
            ]);
        }

        $profileChanged = trim((string) $oldProfile) !== trim((string) $profile);
        $remoteAddressChanged = $this->normalizeRemoteAddress($oldRemoteAddress)
            !== $this->normalizeRemoteAddress($remoteAddress);

        if ($profileChanged || $this->routerBoolean($oldDisabled) || (! $inactive && $remoteAddressChanged) || $activeSessionPoolMismatch) {
            $this->disconnectActiveSession($client, $username);
        }

        return $inactive ? 'moved_inactive' : 'updated';
    }

    public function ensurePppProfile(RouterOsClient $client, string $profile, ?string $defaultIpPool = null, ?string $rateLimit = null): void
    {
        $existing = $client->command('/ppp/profile/print', [
            '?name' => $profile,
            '.proplist' => '.id,name,remote-address,rate-limit',
        ]);

        if ($existing !== []) {
            if (count($existing) !== 1 || trim((string) ($existing[0]['name'] ?? '')) !== trim($profile)) {
                throw new RuntimeException("RouterOS returned a mismatched PPP profile while syncing {$profile}.");
            }

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

    private function remoteAddressFor(Customer $customer, ?InternetPackage $package, ?int $routerId): ?string
    {
        if ($customer->use_fixed_ip) {
            return $customer->fixed_ip_address ?: null;
        }

        if ($package
            && (int) $customer->learned_ip_package_id === $package->id
            && $this->ipMatchesConfiguredPackagePool($package, $customer->learned_ip_address, $routerId)) {
            return $customer->learned_ip_address ?: null;
        }

        return null;
    }

    private function ipMatchesConfiguredPackagePool(
        ?InternetPackage $package,
        ?string $ipAddress,
        ?int $routerId,
    ): bool {
        if (! $ipAddress || ! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $poolName = trim((string) ($package?->default_ip_pool ?? ''));
        if ($poolName === '') {
            return true;
        }

        $cacheKey = ($routerId ?: 'global').'|'.$poolName;
        if (! array_key_exists($cacheKey, $this->poolRangesCache)) {
            $query = AppIpPool::query()->where('name', $poolName);
            if ($routerId) {
                $query->where(function ($query) use ($routerId): void {
                    $query->where('mikrotik_router_id', $routerId)
                        ->orWhereNull('mikrotik_router_id');
                });
            }

            $ranges = $query->pluck('ranges')->filter()->values()->all();
            if ($ranges === [] && $routerId) {
                $ranges = AppIpPool::query()
                    ->where('name', $poolName)
                    ->pluck('ranges')
                    ->filter()
                    ->values()
                    ->all();
            }

            $this->poolRangesCache[$cacheKey] = $ranges;
        }

        $ranges = $this->poolRangesCache[$cacheKey];
        if ($ranges === []) {
            return true;
        }

        $hasUsableRange = false;
        foreach ($ranges as $rangeList) {
            foreach (preg_split('/[\s,;]+/', trim((string) $rangeList), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $range) {
                $matches = $this->ipv4MatchesRange($ipAddress, $range);
                if ($matches === null) {
                    continue;
                }

                $hasUsableRange = true;
                if ($matches) {
                    return true;
                }
            }
        }

        return ! $hasUsableRange;
    }

    private function ipv4MatchesRange(string $ipAddress, string $range): ?bool
    {
        $ip = $this->ipv4ToUnsignedInteger($ipAddress);
        if ($ip === null) {
            return null;
        }

        if (str_contains($range, '/')) {
            [$network, $prefix] = array_pad(explode('/', $range, 2), 2, null);
            $network = $this->ipv4ToUnsignedInteger(trim((string) $network));
            if ($network === null || ! ctype_digit((string) $prefix)) {
                return null;
            }

            $prefix = (int) $prefix;
            if ($prefix < 0 || $prefix > 32) {
                return null;
            }

            $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);

            return ($ip & $mask) === ($network & $mask);
        }

        if (str_contains($range, '-')) {
            [$start, $end] = array_pad(explode('-', $range, 2), 2, null);
            $start = $this->ipv4ToUnsignedInteger(trim((string) $start));
            $end = $this->ipv4ToUnsignedInteger(trim((string) $end));
            if ($start === null || $end === null) {
                return null;
            }

            return $ip >= min($start, $end) && $ip <= max($start, $end);
        }

        $single = $this->ipv4ToUnsignedInteger(trim($range));

        return $single === null ? null : $ip === $single;
    }

    private function ipv4ToUnsignedInteger(string $ipAddress): ?int
    {
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        $value = ip2long($ipAddress);

        return $value === false ? null : (int) sprintf('%u', $value);
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

    private function secretAttributeMatches(string $attribute, mixed $current, mixed $expected): bool
    {
        if ($attribute === 'disabled') {
            return $this->routerBoolean($current) === $this->routerBoolean($expected);
        }

        if ($attribute === 'remote-address') {
            return $this->normalizeRemoteAddress($current) === $this->normalizeRemoteAddress($expected);
        }

        return trim((string) $current) === trim((string) $expected);
    }

    private function routerBoolean(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['true', 'yes', '1', 'on'], true);
    }

    private function normalizeRemoteAddress(mixed $value): ?string
    {
        $value = trim((string) $value);

        return in_array($value, ['', '0.0.0.0'], true) ? null : $value;
    }

    private function assertSingleExactSecret(array $secrets, string $username): void
    {
        if ($secrets === []) {
            throw new RuntimeException("RouterOS did not return PPPoE secret {$username} after it was expected to exist.");
        }

        $names = collect($secrets)
            ->map(fn (array $secret) => trim((string) ($secret['name'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        if (count($secrets) !== 1 || $names->count() !== 1 || $names->first() !== trim($username)) {
            throw new RuntimeException("Duplicate or mismatched PPPoE secrets found for {$username}; sync stopped before writing.");
        }

        if (empty($secrets[0]['.id'])) {
            throw new RuntimeException("RouterOS PPPoE secret {$username} has no stable record ID; sync stopped before writing.");
        }
    }

    private function assertRouterHasUniqueSecretNames(RouterOsClient $client): void
    {
        $duplicates = collect($client->command('/ppp/secret/print', [
            '.proplist' => '.id,name',
        ]))
            ->filter(fn (array $secret) => filled($secret['name'] ?? null))
            ->groupBy(fn (array $secret) => trim((string) $secret['name']))
            ->filter(fn ($records) => $records->count() > 1)
            ->keys()
            ->values();

        if ($duplicates->isNotEmpty()) {
            $examples = $duplicates->take(5)->implode(', ');
            throw new RuntimeException(
                "RouterOS has {$duplicates->count()} duplicate PPPoE username(s) ({$examples}); router sync stopped before writing."
            );
        }
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
