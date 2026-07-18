<?php

namespace App\Services;

use App\Models\AppIpPool;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedIpPool;
use App\Models\MikrotikImportedProfile;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;

class MikrotikImportService
{
    public function liveRecords(MikrotikRouter $router, string $command): array
    {
        return $this->read($router, $command);
    }

    public function write(MikrotikRouter $router, string $command, array $attributes): array
    {
        $client = new RouterOsClient();

        try {
            $client->connect($router->ip_address, $router->api_port, $router->username, $router->password, 10);

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

    private function read(MikrotikRouter $router, string $command): array
    {
        $client = new RouterOsClient();

        try {
            $client->connect($router->ip_address, $router->api_port, $router->username, $router->password, 10);

            return $client->command($command);
        } finally {
            $client->close();
        }
    }
}
