<?php

namespace Tests\Feature;

use App\Models\MikrotikRouter;
use App\Models\PppLiveSession;
use App\Models\PppUsageLog;
use App\Services\MikrotikCustomerSyncService;
use App\Services\PppSessionListenerService;
use App\Services\PppSessionSnapshotService;
use App\Services\RouterOsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PppSessionListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_starts_listen_before_reconciliation_and_finalises_the_dead_event(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = MikrotikRouter::create([
            'name' => 'Native API Router',
            'ip_address' => '10.0.0.51',
            'api_port' => 8728,
            'transport' => 'api',
            'pppoe_sync_interval_days' => 10,
            'inactive_pppoe_profile' => 'inactive',
            'username' => 'api',
            'password' => 'secret',
            'status' => 'active',
        ]);

        $listenerClient = new class extends RouterOsClient
        {
            public array $started = [];

            public array $replies = [
                ['type' => '!re', 'data' => [
                    '.id' => '*A6', 'name' => 'listener-party',
                    'bytes-out' => '10500', 'bytes-in' => '2100',
                ]],
                ['type' => '!re', 'data' => [
                    '.id' => '*A6', '.dead' => 'yes',
                    'bytes-out' => '10999', 'bytes-in' => '2222',
                ]],
            ];

            public function connect(string $host, int $port, string $username, string $password, int $timeout = 5): void {}

            public function startCommand(string $command, array $attributes = []): void
            {
                $this->started[] = $command;
            }

            public function nextReply(int $waitSeconds = 1): ?array
            {
                return array_shift($this->replies);
            }

            public function close(): void {}
        };

        $snapshotClient = new class extends RouterOsClient
        {
            public array $commands = [];

            public function connect(string $host, int $port, string $username, string $password, int $timeout = 5): void {}

            public function command(string $command, array $attributes = []): array
            {
                $this->commands[] = $command;

                return match ($command) {
                    '/ppp/active/print' => [[
                        '.id' => '*A6', 'name' => 'listener-party',
                        'bytes-out' => '10000', 'bytes-in' => '2000',
                    ]],
                    '/interface/print' => [],
                    default => [],
                };
            }

            public function close(): void {}
        };

        $clients = [$listenerClient, $snapshotClient];
        $service = new class(app(PppSessionSnapshotService::class), app(MikrotikCustomerSyncService::class), $clients) extends PppSessionListenerService
        {
            /** @param array<int, RouterOsClient> $clients */
            public function __construct(
                PppSessionSnapshotService $snapshots,
                MikrotikCustomerSyncService $customerSync,
                private array $clients,
            ) {
                parent::__construct($snapshots, $customerSync);
            }

            protected function makeClient(): RouterOsClient
            {
                return array_shift($this->clients);
            }
        };

        $checks = 0;
        $stats = $service->run($router, function () use (&$checks): bool {
            return $checks++ >= 2;
        });

        $this->assertSame(['/ppp/active/listen'], $listenerClient->started);
        $this->assertSame(['/ppp/active/print', '/interface/print'], $snapshotClient->commands);
        $this->assertSame(0, $stats['active']);
        $this->assertSame(2, $stats['events']);
        $this->assertSame(1, $stats['finalised']);

        $log = PppUsageLog::firstOrFail();
        $this->assertSame('listener', $log->source);
        $this->assertSame(10999, $log->download_bytes);
        $this->assertSame(2222, $log->upload_bytes);
    }

    public function test_listener_command_rejects_rest_transport(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = MikrotikRouter::create([
            'name' => 'REST-only Router',
            'ip_address' => '10.0.0.52',
            'api_port' => 8181,
            'transport' => 'rest',
            'pppoe_sync_interval_days' => 10,
            'inactive_pppoe_profile' => 'inactive',
            'username' => 'api',
            'password' => 'secret',
            'status' => 'active',
        ]);

        $this->assertSame(1, Artisan::call('mikrotik:listen-ppp-sessions', [
            'router' => $router->id,
            '--once' => true,
        ]));
        $this->assertStringContainsString('requires the binary API transport', Artisan::output());
    }

    public function test_buffered_sparse_dead_event_wins_over_startup_missing_reconciliation(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = MikrotikRouter::create([
            'name' => 'Startup Race Router',
            'ip_address' => '10.0.0.53',
            'api_port' => 8728,
            'transport' => 'api',
            'pppoe_sync_interval_days' => 10,
            'inactive_pppoe_profile' => 'inactive',
            'username' => 'api',
            'password' => 'secret',
            'status' => 'active',
        ]);

        PppLiveSession::create([
            'mikrotik_router_id' => $router->id,
            'routeros_session_id' => '*RACE',
            'username' => 'race-party',
            'download_bytes' => 5000,
            'upload_bytes' => 500,
            'payload' => ['active' => ['.id' => '*RACE', 'name' => 'race-party']],
            'first_seen_at' => now()->subMinute(),
            'last_seen_at' => now()->subSecond(),
        ]);

        $listenerClient = new class extends RouterOsClient
        {
            public array $replies = [
                ['type' => '!re', 'data' => [
                    '.id' => '*RACE', '.dead' => 'yes',
                    'bytes-out' => '7777', 'bytes-in' => '888',
                ]],
            ];

            public function connect(string $host, int $port, string $username, string $password, int $timeout = 5): void {}

            public function startCommand(string $command, array $attributes = []): void {}

            public function nextReply(int $waitSeconds = 1): ?array
            {
                return array_shift($this->replies);
            }

            public function close(): void {}
        };

        $snapshotClient = new class extends RouterOsClient
        {
            public function connect(string $host, int $port, string $username, string $password, int $timeout = 5): void {}

            public function command(string $command, array $attributes = []): array
            {
                return [];
            }

            public function close(): void {}
        };

        $service = new class(app(PppSessionSnapshotService::class), app(MikrotikCustomerSyncService::class), [$listenerClient, $snapshotClient]) extends PppSessionListenerService
        {
            /** @param array<int, RouterOsClient> $clients */
            public function __construct(
                PppSessionSnapshotService $snapshots,
                MikrotikCustomerSyncService $customerSync,
                private array $clients,
            ) {
                parent::__construct($snapshots, $customerSync);
            }

            protected function makeClient(): RouterOsClient
            {
                return array_shift($this->clients);
            }
        };

        $checks = 0;
        $service->run($router, function () use (&$checks): bool {
            return $checks++ >= 3;
        });

        $this->assertSame(0, PppLiveSession::count());
        $this->assertSame(1, PppUsageLog::count());
        $log = PppUsageLog::firstOrFail();
        $this->assertSame(7777, $log->download_bytes);
        $this->assertSame(888, $log->upload_bytes);
    }
}
