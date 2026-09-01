<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
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
        ]);

        $customer = Customer::create([
            'name' => 'Tunnel Party', 'phone' => '01700000003', 'connection_id' => 'l2tp-1',
            'mikrotik_username' => 'l2tp-1', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $summary = app(MikrotikCustomerSyncService::class)->syncActiveConnectionMacs($router);

        $this->assertSame(0, $summary['updated']);
        $this->assertNull($customer->refresh()->last_connected_mac);
        $this->assertNull($customer->last_connected_ip, 'A non-MAC session must not touch the party at all.');
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
}
