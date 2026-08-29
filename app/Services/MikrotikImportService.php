<?php

namespace App\Services;

use App\Models\AppIpPool;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedIpPool;
use App\Models\MikrotikImportedProfile;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class MikrotikImportService
{
    public function liveRecords(MikrotikRouter $router, string $command): array
    {
        return $this->read($router, $command);
    }

    public function hasPppProfile(MikrotikRouter $router, string $profile): bool
    {
        $profile = trim((string) $profile);
        if ($profile === '') {
            return false;
        }

        $existing = $this->read($router, '/ppp/profile/print', [
            '?name' => $profile,
            '.proplist' => '.id',
        ]);

        return $existing !== [];
    }

    public function createPppProfile(MikrotikRouter $router, string $profile): bool
    {
        $profile = trim((string) $profile);
        if ($profile === '') {
            throw new \InvalidArgumentException('Profile name is required.');
        }

        if ($this->hasPppProfile($router, $profile)) {
            return false;
        }

        $this->write($router, '/ppp/profile/add', ['name' => $profile]);

        return true;
    }

    public function write(MikrotikRouter $router, string $command, array $attributes): array
    {
        $client = new RouterOsClient;

        try {
            $client->connect($router->ip_address, $router->api_port, $router->username, $router->apiPassword(), 10);

            return $client->command($command, $attributes);
        } finally {
            $client->close();
        }
    }

    public function importProfiles(MikrotikRouter $router): int
    {
        $records = $this->read($router, '/ppp/profile/print');

        foreach ($records as $record) {
            $name = trim((string) ($record['name'] ?? ''));
            if ($name === '' || empty($record['.id'])) {
                continue;
            }

            MikrotikImportedProfile::updateOrCreate(
                ['mikrotik_router_id' => $router->id, 'routeros_id' => $record['.id']],
                [
                    'name' => $name,
                    'local_address' => $record['local-address'] ?? null,
                    'remote_address' => $record['remote-address'] ?? null,
                    'rate_limit' => $record['rate-limit'] ?? null,
                    'disabled' => ($record['disabled'] ?? 'false') === 'true',
                    'source_note' => $record['comment'] ?? null,
                    'imported_at' => now(),
                ]
            );

            InternetPackage::firstOrCreate(
                ['mikrotik_profile' => $name],
                [
                    'name' => $name,
                    'speed' => ($record['rate-limit'] ?? null) ?: 'Imported profile',
                    'monthly_price' => 0,
                    'description' => 'Automatically imported from MikroTik '.$router->name.' ('.$router->ip_address.'). Set the package price before billing.',
                    'status' => ($record['disabled'] ?? 'false') === 'true' ? 'inactive' : 'active',
                ]
            );
        }

        return count($records);
    }

    public function importIpPools(MikrotikRouter $router, bool $saveToApp = false): int
    {
        $records = $this->liveRecords($router, '/ip/pool/print');

        foreach ($records as $record) {
            if (empty($record['.id']) || blank($record['name'] ?? null)) {
                continue;
            }

            MikrotikImportedIpPool::updateOrCreate(
                ['mikrotik_router_id' => $router->id, 'routeros_id' => $record['.id']],
                [
                    'name' => $record['name'],
                    'ranges' => $record['ranges'] ?? null,
                    'next_pool' => $record['next-pool'] ?? null,
                    'source_note' => $record['comment'] ?? null,
                    'imported_at' => now(),
                ]
            );

            if ($saveToApp) {
                AppIpPool::updateOrCreate(
                    ['mikrotik_router_id' => $router->id, 'name' => $record['name']],
                    [
                        'ranges' => $record['ranges'] ?? '',
                        'next_pool' => $record['next-pool'] ?? null,
                        'notes' => $record['comment'] ?? null,
                        'status' => 'active',
                    ]
                );
            }
        }

        return count($records);
    }

    public function importSecrets(MikrotikRouter $router): int
    {
        // A secret cannot be usefully converted to a party without its PPP
        // profile, so keep the local profile/package list in sync first.
        $this->importProfiles($router);
        $records = $this->read($router, '/ppp/secret/print');

        foreach ($records as $record) {
            if (empty($record['.id']) || blank($record['name'] ?? null)) {
                continue;
            }

            MikrotikImportedSecret::updateOrCreate(
                ['mikrotik_router_id' => $router->id, 'routeros_id' => $record['.id']],
                [
                    'name' => $record['name'],
                    'password' => $record['password'] ?? null,
                    'service' => $record['service'] ?? null,
                    'profile' => $record['profile'] ?? null,
                    'local_address' => $record['local-address'] ?? null,
                    'remote_address' => $record['remote-address'] ?? null,
                    'disabled' => ($record['disabled'] ?? 'false') === 'true',
                    'router_comment' => $record['comment'] ?? null,
                    'imported_at' => now(),
                ]
            );
        }

        return count($records);
    }

    /**
     * Re-pull PPPoE secrets from every active router.
     *
     * @return array{results: array<int, array{router: string, count?: int, error?: string}>, imported: int, failed: int}
     */
    public function refreshActiveRouterSecrets(): array
    {
        $results = [];
        $imported = 0;
        $failed = 0;

        MikrotikRouter::query()->where('status', 'active')->orderBy('id')->get()
            ->each(function (MikrotikRouter $router) use (&$results, &$imported, &$failed): void {
                try {
                    $count = $this->importSecrets($router);
                    $imported += $count;
                    $results[] = ['router' => $router->name, 'count' => $count];
                } catch (Throwable $exception) {
                    $failed++;
                    $results[] = ['router' => $router->name, 'error' => $exception->getMessage()];
                }
            });

        return ['results' => $results, 'imported' => $imported, 'failed' => $failed];
    }

    /**
     * Imported PPPoE secrets that are not linked to, and do not name-match, any
     * app party — i.e. router users that exist only on the MikroTik.
     */
    public function unmanagedSecretsQuery(): Builder
    {
        return MikrotikImportedSecret::query()
            ->whereNull('customer_id')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('customers')
                    ->whereNull('customers.deleted_at')
                    ->whereRaw(
                        '(lower(customers.connection_id) = lower(mikrotik_imported_secrets.name)'
                        .' or lower(customers.mikrotik_username) = lower(mikrotik_imported_secrets.name))'
                    );
            });
    }

    /**
     * @return Collection<int, MikrotikImportedSecret>
     */
    public function unmanagedSecrets(): Collection
    {
        return $this->unmanagedSecretsQuery()
            ->with('router:id,name,ip_address,read_only')
            ->orderBy('mikrotik_router_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create (or optionally update) app parties from imported PPPoE secrets.
     * Extracted so both the router page and the dashboard can reuse it.
     *
     * @param  Collection<int, MikrotikImportedSecret>  $secrets
     * @return array{created: int, updated: int, skipped: int}
     */
    public function createPartiesFromSecrets(Collection $secrets, bool $neverSuspend = false, bool $updateExisting = false): array
    {
        $created = $updated = $skipped = 0;

        DB::transaction(function () use ($secrets, $neverSuspend, $updateExisting, &$created, &$updated, &$skipped): void {
            foreach ($secrets as $secret) {
                $router = $secret->router;
                $name = trim((string) $secret->name);

                if ($name === '') {
                    $skipped++;

                    continue;
                }

                // Match any party that already holds this identifier on either
                // column, case-insensitively, and including soft-deleted rows —
                // the unique indexes on connection_id / mikrotik_username also
                // cover trashed parties, so an unchecked collision would 500 the
                // whole batch on insert. Prefer a live party over a trashed one.
                $lowerName = mb_strtolower($name);
                $customer = Customer::withTrashed()
                    ->where(function ($query) use ($lowerName): void {
                        $query->whereRaw('lower(connection_id) = ?', [$lowerName])
                            ->orWhereRaw('lower(mikrotik_username) = ?', [$lowerName]);
                    })
                    ->orderByRaw('deleted_at is null desc')
                    ->orderByDesc('id')
                    ->first();

                // A deleted party still owns the unique identifier, so we cannot
                // insert a fresh one. The router user is live, so bring the party
                // back and refresh it from the router.
                $restored = false;
                if ($customer && $customer->trashed()) {
                    $customer->restore();
                    $restored = true;
                }

                if ($customer && ! $restored && ! $updateExisting) {
                    $skipped++;
                    $secret->update(['customer_id' => $customer->id]);

                    continue;
                }

                $package = $this->packageForProfile($secret->profile, $router);
                $note = $this->importSourceNote($router, $secret);
                $customerData = [
                    'name' => $name,
                    'phone' => $customer?->phone ?: 'Not provided',
                    'connection_id' => $name,
                    'mikrotik_username' => $name,
                    'mikrotik_password' => $secret->password,
                    'mikrotik_router_id' => $router?->id,
                    'address' => $customer?->address ?: 'Imported from MikroTik '.($router?->name ?? 'router'),
                    'notes' => $this->appendImportNote($customer?->notes, $note),
                    'status' => $secret->disabled ? 'inactive' : 'active',
                    'is_customer' => true,
                    'is_vendor' => $customer?->is_vendor ?? false,
                    'never_suspend' => $neverSuspend,
                ];

                if ($neverSuspend) {
                    $customerData['status'] = 'active';
                }

                if ($customer) {
                    $customer->update($customerData);
                    $updated++;
                } else {
                    $customer = Customer::create($customerData);
                    $created++;
                }

                $this->attachSubscription($customer, $package, $secret->disabled && ! $neverSuspend);
                $secret->update(['customer_id' => $customer->id]);
            }
        });

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    public function packageForProfile(?string $profile, ?MikrotikRouter $router): ?InternetPackage
    {
        if (blank($profile)) {
            return null;
        }

        return InternetPackage::firstOrCreate(
            ['mikrotik_profile' => $profile],
            [
                'name' => $profile,
                'speed' => 'Imported profile',
                'monthly_price' => 0,
                'description' => 'Automatically imported from MikroTik '.($router?->name ?? 'router').'. Set the package price before billing.',
                'status' => 'active',
            ]
        );
    }

    private function attachSubscription(Customer $customer, ?InternetPackage $package, bool $inactive): void
    {
        if (! $package) {
            return;
        }

        $values = [
            'internet_package_id' => $package->id,
            'start_date' => now()->toDateString(),
            'status' => $inactive ? 'inactive' : 'active',
        ];

        $subscription = $customer->subscriptions()->latest('id')->first();

        if ($subscription) {
            $subscription->update($values);
        } else {
            Subscription::create(['customer_id' => $customer->id, ...$values]);
        }
    }

    private function importSourceNote(?MikrotikRouter $router, MikrotikImportedSecret $secret): string
    {
        return 'Imported from MikroTik: '.($router?->name ?? 'router')
            .' ('.($router?->ip_address ?? '?').':'.($router?->api_port ?? '?').') at '.now()->format('d/m/Y H:i:s')
            ."\nConnection ID: {$secret->name}\nProfile: ".($secret->profile ?: 'none')
            ."\nService: ".($secret->service ?: 'none')
            ."\nRouter comment: ".($secret->router_comment ?: 'none');
    }

    private function appendImportNote(?string $old, string $new): string
    {
        return trim(($old ? rtrim($old)."\n\n" : '').$new);
    }

    private function read(MikrotikRouter $router, string $command, array $attributes = []): array
    {
        $client = new RouterOsClient;

        try {
            $client->connect($router->ip_address, $router->api_port, $router->username, $router->apiPassword(), 10);

            return $client->command($command, $attributes);
        } finally {
            $client->close();
        }
    }
}
