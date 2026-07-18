<?php

namespace Tests\Feature;

use App\Models\AppIpPool;
use App\Models\Customer;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\User;
use App\Services\MikrotikImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MikrotikImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_imported_secret_creates_a_party_with_router_source_and_special_selection(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Core Router', 'ip_address' => '10.0.0.1', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        $secret = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'routeros_id' => '*A1', 'name' => 'pppoe-100',
            'password' => 'pppoe-pass', 'service' => 'pppoe', 'profile' => '20M',
            'router_comment' => 'Imported Customer', 'disabled' => false, 'imported_at' => now(),
        ]);

        $this->actingAs($user)->post(route('mikrotik-routers.imported-secrets.create-parties', $router), [
            'secret_ids' => [$secret->id], 'never_suspend' => '1',
        ])->assertRedirect();

        $customer = Customer::where('connection_id', 'pppoe-100')->firstOrFail();
        $this->assertSame($router->id, $customer->mikrotik_router_id);
        $this->assertSame('pppoe-pass', $customer->mikrotik_password);
        $this->assertTrue($customer->never_suspend);
        $this->assertStringContainsString('Imported from MikroTik: Core Router', $customer->notes);
        $this->assertDatabaseHas('subscriptions', ['customer_id' => $customer->id]);
        $this->assertDatabaseHas('mikrotik_imported_secrets', ['id' => $secret->id, 'customer_id' => $customer->id]);
    }

    public function test_compare_page_remains_available_when_the_router_api_fails(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Offline Router', 'ip_address' => '10.0.0.2', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        $service = $this->mock(MikrotikImportService::class);
        $service->shouldReceive('liveRecords')
            ->once()
            ->andThrow(new RuntimeException('Connection refused'));

        $this->actingAs($user)
            ->get(route('mikrotik-routers.compare', $router))
            ->assertOk()
            ->assertSee('MikroTik live data is unavailable')
            ->assertSee('Live-dependent actions are temporarily disabled.');
    }

    public function test_bulk_ip_pool_import_saves_every_live_pool_to_the_app_and_returns_to_pool_section(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Pool Router', 'ip_address' => '10.0.0.4', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        $service = $this->partialMock(MikrotikImportService::class);
        $service->shouldReceive('liveRecords')
            ->once()
            ->withArgs(fn (MikrotikRouter $givenRouter, string $command) =>
                $givenRouter->is($router) && $command === '/ip/pool/print')
            ->andReturn([
                ['.id' => '*1', 'name' => 'customers', 'ranges' => '10.10.0.2-10.10.0.254', 'next-pool' => 'overflow'],
                ['.id' => '*2', 'name' => 'overflow', 'ranges' => '10.10.1.2-10.10.1.254', 'comment' => 'Backup pool'],
            ]);

        $this->actingAs($user)->post(route('mikrotik-routers.import.ip-pools', $router), [
            'save_to_app' => '1',
            'return_to_compare' => '1',
        ])->assertRedirect(route('mikrotik-routers.compare', $router).'#ip-pools');

        $this->assertDatabaseHas('app_ip_pools', [
            'mikrotik_router_id' => $router->id,
            'name' => 'customers',
            'ranges' => '10.10.0.2-10.10.0.254',
            'next_pool' => 'overflow',
        ]);
        $this->assertDatabaseHas('app_ip_pools', [
            'mikrotik_router_id' => $router->id,
            'name' => 'overflow',
            'notes' => 'Backup pool',
        ]);
        $this->assertSame(2, AppIpPool::where('mikrotik_router_id', $router->id)->count());
    }

    public function test_single_ip_pool_import_returns_to_pool_section(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Pool Router', 'ip_address' => '10.0.0.5', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        $service = $this->mock(MikrotikImportService::class);
        $service->shouldReceive('liveRecords')
            ->once()
            ->withArgs(fn (MikrotikRouter $givenRouter, string $command) =>
                $givenRouter->is($router) && $command === '/ip/pool/print')
            ->andReturn([
                ['.id' => '*A', 'name' => 'staff', 'ranges' => '172.16.1.2-172.16.1.100'],
            ]);

        $this->actingAs($user)->post(route('mikrotik-routers.pools.import-live', $router), [
            'routeros_id' => '*A',
        ])->assertRedirect(route('mikrotik-routers.compare', $router).'#ip-pools');

        $this->assertDatabaseHas('app_ip_pools', [
            'mikrotik_router_id' => $router->id,
            'name' => 'staff',
        ]);
    }

    public function test_router_edit_ignores_browser_login_autofill_fields(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Main Router', 'ip_address' => '10.0.0.3', 'api_port' => 8787,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'admin', 'password' => 'router-secret', 'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('mikrotik-routers.edit', $router))
            ->assertOk()
            ->assertSee('name="router_api_username"', false)
            ->assertSee('data-saved-username="admin"', false)
            ->assertSee('name="router_api_password"', false);

        $this->actingAs($user)->put(route('mikrotik-routers.update', $router), [
            'name' => 'Main Router', 'ip_address' => '10.0.0.3', 'api_port' => 8787,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'router_api_username' => 'admin', 'router_api_password' => '',
            'username' => 'anike10@gmail.com', 'password' => 'website-login-password',
            'status' => 'active', 'notes' => null,
        ])->assertRedirect(route('mikrotik-routers.show', $router));

        $router->refresh();
        $this->assertSame('admin', $router->username);
        $this->assertSame('router-secret', $router->password);
    }
}
