<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\PppLiveSession;
use App\Models\PppUsageLog;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
use App\Services\PppSessionSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MikrotikActiveMacSyncTest extends TestCase
{
    use RefreshDatabase;

    private function restRouter(string $ip, array $overrides = []): MikrotikRouter
    {
        return MikrotikRouter::create(array_merge([
            'name' => 'REST Router', 'ip_address' => $ip, 'api_port' => 8181,
            'transport' => 'rest', 'pppoe_sync_interval_days' => 10,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'anike', 'password' => 'reader-pass',
            'status' => 'active', 'read_only' => true,
        ], $overrides));
    }

    public function test_active_mac_sync_copies_the_device_mac_onto_the_matching_party(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.40');

        Http::fake([
            '10.0.0.40:8181/rest/ppp/active' => Http::response([
                ['.id' => '*1', 'name' => 'pppoe-1', 'caller-id' => '00:8D:FF:02:2A:17', 'address' => '10.9.0.5'],
                ['.id' => '*2', 'name' => 'ghost-9', 'caller-id' => 'aa:bb:cc:dd:ee:ff'],
            ], 200),
            '10.0.0.40:8181/rest/interface' => Http::response([], 200),
        ]);

        $customer = Customer::create([
            'name' => 'Fiber Party', 'phone' => '01700000001', 'connection_id' => 'pppoe-1',
            'mikrotik_username' => 'pppoe-1', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $summary = app(MikrotikCustomerSyncService::class)->syncActiveConnectionMacs($router);

        $this->assertSame(
            ['sessions' => 2, 'no_mac' => 0, 'matched' => 1, 'unmatched' => 1, 'updated' => 1],
            $summary
        );

        $customer->refresh();
        $this->assertSame('00:8D:FF:02:2A:17', $customer->last_connected_mac);
        $this->assertSame('10.9.0.5', $customer->last_connected_ip);
        $this->assertNotNull($customer->last_connected_at);
    }

    public function test_active_mac_sync_reports_no_change_when_the_mac_is_already_current(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.41');

        Http::fake([
            '10.0.0.41:8181/rest/ppp/active' => Http::response([
                ['.id' => '*1', 'name' => 'pppoe-2', 'caller-id' => '00:11:22:33:44:55'],
            ], 200),
            '10.0.0.41:8181/rest/interface' => Http::response([], 200),
        ]);

        Customer::create([
            'name' => 'Steady Party', 'phone' => '01700000002', 'connection_id' => 'pppoe-2',
            'mikrotik_username' => 'pppoe-2', 'mikrotik_router_id' => $router->id,
            'last_connected_mac' => '00:11:22:33:44:55',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $summary = app(MikrotikCustomerSyncService::class)->syncActiveConnectionMacs($router);

        $this->assertSame(1, $summary['sessions']);
        $this->assertSame(0, $summary['updated']);
    }

    public function test_active_mac_sync_skips_a_non_mac_caller_id(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.44');

        Http::fake([
            '10.0.0.44:8181/rest/ppp/active' => Http::response([
                ['.id' => '*1', 'name' => 'l2tp-1', 'caller-id' => '203.0.113.7', 'address' => '10.9.0.1'],
            ], 200),
            '10.0.0.44:8181/rest/interface' => Http::response([], 200),
        ]);

        $customer = Customer::create([
            'name' => 'Tunnel Party', 'phone' => '01700000003', 'connection_id' => 'l2tp-1',
            'mikrotik_username' => 'l2tp-1', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $summary = app(MikrotikCustomerSyncService::class)->syncActiveConnectionMacs($router);

        $this->assertSame(0, $summary['updated']);
        $this->assertNull($customer->refresh()->last_connected_mac);
        $this->assertSame('10.9.0.1', $customer->last_connected_ip, 'IP history must update even when caller-id is not a MAC.');
    }

    public function test_router_edit_form_no_longer_exposes_an_active_mac_sync_interval(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Editable', 'ip_address' => '10.0.0.42', 'api_port' => 8728,
            'pppoe_sync_interval_days' => 10,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        $base = [
            'name' => 'Editable', 'ip_address' => '10.0.0.42', 'api_port' => 8728,
            'pppoe_sync_interval_days' => 10, 'inactive_pppoe_profile' => 'inactive',
            'router_api_username' => 'api', 'router_api_password' => '', 'status' => 'active', 'notes' => null,
        ];

        $this->actingAs($user)
            ->get(route('mikrotik-routers.edit', $router))
            ->assertOk()
            ->assertDontSee('name="active_mac_sync_interval_days"', false);

        // The field is now ignored, not validated: an out-of-range value no
        // longer blocks the save.
        $this->actingAs($user)
            ->from(route('mikrotik-routers.edit', $router))
            ->put(route('mikrotik-routers.update', $router), $base + ['active_mac_sync_interval_days' => 400])
            ->assertRedirect(route('mikrotik-routers.show', $router))
            ->assertSessionHasNoErrors();
    }

    public function test_sync_active_macs_command_runs_for_every_active_router_each_time(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.43');

        Http::fake([
            '10.0.0.43:8181/rest/ppp/active' => Http::response([], 200),
            '10.0.0.43:8181/rest/interface' => Http::response([], 200),
        ]);

        Artisan::call('mikrotik:sync-active-macs');
        $this->assertNotNull($router->refresh()->last_active_mac_sync_at);
        $firstRun = $router->last_active_mac_sync_at;

        // No per-router interval gate any more: it runs again on the next dispatch.
        $this->travel(1)->hours();
        Artisan::call('mikrotik:sync-active-macs');
        $this->assertTrue($router->refresh()->last_active_mac_sync_at->greaterThan($firstRun));
    }

    public function test_sync_active_macs_for_customer_polls_only_its_own_router(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.45');

        Http::fake([
            '10.0.0.45:8181/rest/ppp/active' => Http::response([
                ['.id' => '*1', 'name' => 'pppoe-new', 'caller-id' => '00:8D:FF:02:2A:99', 'address' => '10.9.0.7'],
            ], 200),
            '10.0.0.45:8181/rest/interface' => Http::response([], 200),
        ]);

        $customer = Customer::create([
            'name' => 'Brand New', 'phone' => '01700000009', 'connection_id' => 'pppoe-new',
            'mikrotik_username' => 'pppoe-new', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $summary = app(MikrotikCustomerSyncService::class)->syncActiveConnectionMacsForCustomer($customer);

        $this->assertSame(1, $summary['updated']);
        $this->assertSame('00:8D:FF:02:2A:99', $customer->refresh()->last_connected_mac);
    }

    public function test_active_poll_finalises_the_last_byte_snapshot_when_a_session_disappears(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.46');

        Http::fake([
            '10.0.0.46:8181/rest/ppp/active' => Http::sequence()
                ->push([[
                    '.id' => '*A1', 'name' => 'usage-1',
                    'service' => 'pppoe',
                    'caller-id' => '00:8D:FF:02:2A:46', 'uptime' => '1h2m3s',
                ]], 200)
                ->push([], 200),
            '10.0.0.46:8181/rest/interface' => Http::sequence()
                ->push([[
                    '.id' => '*F1', 'name' => '<pppoe-usage-1>', 'type' => 'pppoe-in',
                    'rx-byte' => '2097152', 'tx-byte' => '10485760',
                ]], 200)
                ->push([], 200),
        ]);

        $customer = Customer::create([
            'name' => 'Usage Party', 'phone' => '01700000046', 'connection_id' => 'usage-1',
            'mikrotik_username' => 'usage-1', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $service = app(MikrotikCustomerSyncService::class);
        $service->syncActiveConnectionMacs($router);

        $snapshot = PppLiveSession::firstOrFail();
        $this->assertSame(10485760, $snapshot->download_bytes);
        $this->assertSame(2097152, $snapshot->upload_bytes);
        $this->assertSame(3723, $snapshot->uptime_seconds);
        $this->assertSame($customer->id, $snapshot->customer_id);

        $service->syncActiveConnectionMacs($router);

        $this->assertSame(0, PppLiveSession::count());
        $log = PppUsageLog::firstOrFail();
        $this->assertSame('snapshot', $log->source);
        $this->assertSame('*A1', $log->routeros_session_id);
        $this->assertSame(10485760, $log->download_bytes);
        $this->assertSame(2097152, $log->upload_bytes);
        $this->assertSame(3723, $log->uptime_seconds);
    }

    public function test_active_poll_merges_into_a_recent_webhook_row_instead_of_double_counting(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.47');

        Http::fake([
            '10.0.0.47:8181/rest/ppp/active' => Http::sequence()
                ->push([[
                    '.id' => '*A2', 'name' => 'usage-2', 'uptime' => '5m',
                    'bytes-in' => '2000', 'bytes-out' => '9000',
                ]], 200)
                ->push([], 200),
            '10.0.0.47:8181/rest/interface' => Http::response([], 200),
        ]);

        $service = app(MikrotikCustomerSyncService::class);
        $service->syncActiveConnectionMacs($router);

        PppUsageLog::create([
            'mikrotik_router_id' => $router->id,
            'username' => 'usage-2',
            'source' => 'webhook',
            'download_bytes' => 0,
            'upload_bytes' => 0,
            'payload' => ['user' => 'usage-2'],
            'disconnected_at' => now(),
        ]);

        $service->syncActiveConnectionMacs($router);

        $this->assertSame(1, PppUsageLog::count());
        $log = PppUsageLog::firstOrFail();
        $this->assertSame('webhook+snapshot', $log->source);
        $this->assertSame(9000, $log->download_bytes);
        $this->assertSame(2000, $log->upload_bytes);
    }

    public function test_listener_dead_event_uses_its_exact_final_byte_counters(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.49');
        $service = app(PppSessionSnapshotService::class);

        $this->assertSame('added', $service->applyEvent($router, [
            '.id' => '*A4',
            'name' => 'stream-usage',
            'uptime' => '10m',
            'bytes' => '10000/2000',
        ]));

        $this->assertSame('finalised', $service->applyEvent($router, [
            '.id' => '*A4',
            '.dead' => 'yes',
            'name' => 'stream-usage',
            'uptime' => '10m8s',
            'bytes-out' => '12555',
            'bytes-in' => '2444',
            'last-disconnect-reason' => 'peer-request',
        ]));

        $this->assertSame(0, PppLiveSession::count());
        $log = PppUsageLog::firstOrFail();
        $this->assertSame('listener', $log->source);
        $this->assertSame('*A4', $log->routeros_session_id);
        $this->assertSame(12555, $log->download_bytes);
        $this->assertSame(2444, $log->upload_bytes);
        $this->assertSame(608, $log->uptime_seconds);
        $this->assertSame('peer-request', $log->disconnect_reason);
    }

    public function test_sparse_listener_dead_event_retains_the_last_complete_record(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.50');
        $service = app(PppSessionSnapshotService::class);

        $service->applyEvent($router, [
            '.id' => '*A5', 'name' => 'sparse-dead',
            'bytes-out' => '9000', 'bytes-in' => '1500',
        ]);
        $service->applyEvent($router, ['.id' => '*A5', '.dead' => 'yes']);

        $log = PppUsageLog::firstOrFail();
        $this->assertSame(9000, $log->download_bytes);
        $this->assertSame(1500, $log->upload_bytes);
    }
}
