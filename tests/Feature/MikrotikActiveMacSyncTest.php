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
            'active_mac_sync_interval_days' => 1,
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

        $this->assertSame(['sessions' => 2, 'updated' => 1, 'unmatched' => 1], $summary);

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

    public function test_router_edit_form_saves_the_active_mac_sync_interval(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Editable', 'ip_address' => '10.0.0.42', 'api_port' => 8728,
            'pppoe_sync_interval_days' => 10, 'active_mac_sync_interval_days' => 1,
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
            ->assertSee('name="active_mac_sync_interval_days"', false);

        // A value out of range is rejected.
        $this->actingAs($user)
            ->from(route('mikrotik-routers.edit', $router))
            ->put(route('mikrotik-routers.update', $router), $base + ['active_mac_sync_interval_days' => 400])
            ->assertSessionHasErrors('active_mac_sync_interval_days');

        // A valid value is saved; omitting it keeps the current value.
        $this->actingAs($user)
            ->put(route('mikrotik-routers.update', $router), $base + ['active_mac_sync_interval_days' => 7])
            ->assertRedirect(route('mikrotik-routers.show', $router));
        $this->assertSame(7, $router->refresh()->active_mac_sync_interval_days);

        $this->actingAs($user)->put(route('mikrotik-routers.update', $router), $base)->assertRedirect();
        $this->assertSame(7, $router->refresh()->active_mac_sync_interval_days);
    }

    public function test_sync_active_macs_command_gates_on_the_router_interval(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.43');

        Http::fake([
            '10.0.0.43:8181/rest/ppp/active' => Http::response([], 200),
        ]);

        Artisan::call('mikrotik:sync-active-macs');
        $this->assertNotNull($router->refresh()->last_active_mac_sync_at);
        $firstRun = $router->last_active_mac_sync_at;

        // Still inside the 1-day interval: skipped, timestamp unchanged.
        $this->travel(6)->hours();
        Artisan::call('mikrotik:sync-active-macs');
        $this->assertEquals($firstRun, $router->refresh()->last_active_mac_sync_at);

        // Past the interval: runs again on its own.
        $this->travel(2)->days();
        Artisan::call('mikrotik:sync-active-macs');
        $this->assertTrue($router->refresh()->last_active_mac_sync_at->greaterThan($firstRun));

        // --force runs regardless of the interval.
        $forcedFrom = $router->last_active_mac_sync_at;
        $this->travel(1)->minutes();
        Artisan::call('mikrotik:sync-active-macs', ['--force' => true]);
        $this->assertTrue($router->refresh()->last_active_mac_sync_at->greaterThan($forcedFrom));
    }
}
