<?php

namespace Tests\Feature;

use App\Models\AppIpPool;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
use App\Services\MikrotikImportService;
use App\Services\RouterOsRestClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MikrotikImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_router_form_and_database_default_to_ten_day_sync(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());

        $this->actingAs($user)
            ->get(route('mikrotik-routers.create', absolute: false))
            ->assertOk()
            ->assertSee('name="pppoe_sync_interval_days" value="10"', false);

        $router = MikrotikRouter::create([
            'name' => 'Default Interval Router', 'ip_address' => '10.0.0.60', 'api_port' => 8728,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'api', 'password' => 'secret',
            'status' => 'active',
        ]);

        $this->assertSame(10, $router->refresh()->pppoe_sync_interval_days);
    }

    public function test_selected_imported_secret_creates_a_party_with_router_source_and_special_selection(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Core Router', 'ip_address' => '10.0.0.1', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
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
        $this->assertSame('pppoe-100', $customer->name);
        $this->assertSame($router->id, $customer->mikrotik_router_id);
        $this->assertSame('pppoe-pass', $customer->mikrotik_password);
        $this->assertTrue($customer->never_suspend);
        $this->assertStringContainsString('Imported from MikroTik: Core Router', $customer->notes);
        $this->assertStringContainsString('Imported Customer', $customer->notes);
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
            ->withArgs(fn (MikrotikRouter $givenRouter, string $command) => $givenRouter->is($router) && $command === '/ip/pool/print')
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
            ->withArgs(fn (MikrotikRouter $givenRouter, string $command) => $givenRouter->is($router) && $command === '/ip/pool/print')
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

    public function test_read_only_router_still_imports_and_shows_read_only_status(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());

        $router = MikrotikRouter::create([
            'name' => 'RO Router', 'ip_address' => '10.0.0.55', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'reader', 'password' => 'secret', 'status' => 'active',
        ]);

        // Mark it read-only through the edit form.
        $this->actingAs($user)->put(route('mikrotik-routers.update', $router), [
            'name' => 'RO Router', 'ip_address' => '10.0.0.55', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'router_api_username' => 'reader', 'router_api_password' => '',
            'status' => 'active', 'read_only' => '1', 'notes' => null,
        ])->assertRedirect(route('mikrotik-routers.show', $router));
        $this->assertTrue((bool) $router->refresh()->read_only);

        MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'routeros_id' => '*R1', 'name' => 'ro-user-1',
            'password' => 'x', 'service' => 'pppoe', 'profile' => '10M', 'disabled' => false, 'imported_at' => now(),
        ]);

        // The imported-secrets page: import controls present, status reads "Read-only".
        $this->actingAs($user)->get(route('mikrotik-routers.imported-secrets.index', $router))
            ->assertOk()
            ->assertSee('Refresh Secrets')
            ->assertSee('Create Selected Parties')
            ->assertSee('Read-only')
            ->assertDontSee('>Enabled<', false);

        // The router list flags it too.
        $this->actingAs($user)->get(route('mikrotik-routers.index'))
            ->assertOk()
            ->assertSee('Read-only');
    }

    public function test_router_form_saves_rest_transport_without_forcing_read_only(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());

        $this->actingAs($user)->post(route('mikrotik-routers.store'), [
            'name' => 'REST Router', 'ip_address' => '103.133.200.177', 'api_port' => 8181,
            'transport' => 'rest', 'rest_secure' => '1',
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'router_api_username' => 'anike', 'router_api_password' => 'reader-pass',
            'status' => 'active', 'notes' => null,
        ])->assertRedirect(route('mikrotik-routers.index'));

        $router = MikrotikRouter::where('ip_address', '103.133.200.177')->firstOrFail();
        $this->assertSame('rest', $router->transport);
        $this->assertTrue((bool) $router->rest_secure);
        $this->assertFalse((bool) $router->read_only, 'REST no longer forces read-only; the checkbox governs it.');
        $this->assertSame('http://103.133.200.177:8181', (new MikrotikRouter([
            'ip_address' => '103.133.200.177', 'api_port' => 8181, 'rest_secure' => false,
        ]))->restBaseUrl());
    }

    public function test_rest_read_only_flag_is_still_honoured_when_ticked(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());

        $this->actingAs($user)->post(route('mikrotik-routers.store'), [
            'name' => 'RO REST Router', 'ip_address' => '103.133.200.178', 'api_port' => 8181,
            'transport' => 'rest', 'read_only' => '1',
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'router_api_username' => 'anike', 'router_api_password' => 'reader-pass',
            'status' => 'active', 'notes' => null,
        ])->assertRedirect(route('mikrotik-routers.index'));

        $this->assertTrue((bool) MikrotikRouter::where('ip_address', '103.133.200.178')->firstOrFail()->read_only);
    }

    public function test_rest_transport_router_imports_secrets_over_the_www_service(): void
    {
        Http::fake([
            '10.0.0.77:8181/rest/ppp/profile' => Http::response([
                ['.id' => '*1', 'name' => 'P1', 'rate-limit' => '10M/10M', 'disabled' => 'false'],
            ], 200),
            '10.0.0.77:8181/rest/ppp/secret' => Http::response([
                ['.id' => '*1', 'name' => 'noc', 'password' => '1234', 'service' => 'any',
                    'profile' => 'P1', 'disabled' => 'false', 'comment' => 'core user'],
                ['.id' => '*2', 'name' => 'shop', 'password' => 'abcd', 'service' => 'pppoe',
                    'profile' => 'P1', 'disabled' => 'true'],
            ], 200),
            '10.0.0.77:8181/rest/ppp/active' => Http::response([
                ['.id' => '*1', 'name' => 'noc', 'caller-id' => '00:11:22:33:44:55', 'address' => '10.0.0.9'],
            ], 200),
        ]);

        $router = MikrotikRouter::create([
            'name' => 'REST Router', 'ip_address' => '10.0.0.77', 'api_port' => 8181,
            'transport' => 'rest', 'pppoe_sync_interval_minutes' => 60,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'anike', 'password' => 'reader-pass',
            'status' => 'active', 'read_only' => true,
        ]);

        $imported = app(MikrotikImportService::class)->importSecrets($router);

        $this->assertSame(2, $imported);
        $this->assertDatabaseHas('mikrotik_imported_secrets', [
            'mikrotik_router_id' => $router->id, 'name' => 'noc', 'profile' => 'P1', 'disabled' => false,
        ]);
        $this->assertDatabaseHas('mikrotik_imported_secrets', [
            'mikrotik_router_id' => $router->id, 'name' => 'shop', 'disabled' => true,
        ]);
        $noc = MikrotikImportedSecret::where('mikrotik_router_id', $router->id)->where('name', 'noc')->firstOrFail();
        $this->assertSame('1234', $noc->password);
        $this->assertSame('core user', $noc->router_comment);
        $this->assertSame('00:11:22:33:44:55', $noc->device_mac, 'device MAC is enriched from /ppp/active');
        $this->assertDatabaseHas('mikrotik_imported_profiles', [
            'mikrotik_router_id' => $router->id, 'name' => 'P1', 'rate_limit' => '10M/10M',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'http://10.0.0.77:8181/rest/ppp/secret'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('anike:reader-pass')));
    }

    public function test_secret_refresh_removes_stale_real_secrets_but_keeps_active_session_rows(): void
    {
        Http::fake([
            '10.0.0.79:8181/rest/ppp/profile' => Http::response([], 200),
            '10.0.0.79:8181/rest/ppp/secret' => Http::response([
                ['.id' => '*KEEP', 'name' => 'still-live', 'service' => 'pppoe',
                    'profile' => '20M', 'disabled' => 'false'],
            ], 200),
            '10.0.0.79:8181/rest/ppp/active' => Http::response([], 200),
        ]);

        $router = MikrotikRouter::create([
            'name' => 'Reconcile Router', 'ip_address' => '10.0.0.79', 'api_port' => 8181,
            'transport' => 'rest', 'inactive_pppoe_profile' => 'inactive',
            'username' => 'reader', 'password' => 'reader-pass', 'status' => 'active', 'read_only' => true,
        ]);
        $otherRouter = MikrotikRouter::create([
            'name' => 'Other Router', 'ip_address' => '10.0.0.80', 'api_port' => 8181,
            'transport' => 'rest', 'inactive_pppoe_profile' => 'inactive',
            'username' => 'reader', 'password' => 'reader-pass', 'status' => 'inactive', 'read_only' => true,
        ]);

        foreach ([
            [$router, '*KEEP', 'old-live'],
            [$router, '*STALE', 'deleted-on-router'],
            [$router, 'active-*A1', 'radius-session'],
            [$otherRouter, '*OTHER', 'other-router-user'],
        ] as [$owner, $routerOsId, $name]) {
            MikrotikImportedSecret::create([
                'mikrotik_router_id' => $owner->id,
                'routeros_id' => $routerOsId,
                'name' => $name,
                'service' => 'pppoe',
                'disabled' => false,
                'imported_at' => now()->subDay(),
            ]);
        }

        $this->assertSame(1, app(MikrotikImportService::class)->importSecrets($router));

        $this->assertDatabaseHas('mikrotik_imported_secrets', [
            'mikrotik_router_id' => $router->id, 'routeros_id' => '*KEEP', 'name' => 'still-live',
        ]);
        $this->assertDatabaseMissing('mikrotik_imported_secrets', [
            'mikrotik_router_id' => $router->id, 'routeros_id' => '*STALE',
        ]);
        $this->assertDatabaseHas('mikrotik_imported_secrets', [
            'mikrotik_router_id' => $router->id, 'routeros_id' => 'active-*A1',
        ]);
        $this->assertDatabaseHas('mikrotik_imported_secrets', [
            'mikrotik_router_id' => $otherRouter->id, 'routeros_id' => '*OTHER',
        ]);
    }

    public function test_import_active_users_adds_connected_users_without_a_secret_and_keeps_real_secrets(): void
    {
        Http::fake([
            '10.0.0.90:8181/rest/ppp/profile' => Http::response([
                ['.id' => '*1', 'name' => 'P1', 'rate-limit' => '10M/10M', 'disabled' => 'false'],
            ], 200),
            '10.0.0.90:8181/rest/ppp/active' => Http::response([
                ['.id' => '*A1', 'name' => 'noc', 'service' => 'pppoe', 'profile' => 'P1', 'address' => '10.9.0.5', 'caller-id' => 'AA:BB:CC:00:00:01'],
                ['.id' => '*A2', 'name' => 'walkin', 'service' => 'pppoe', 'profile' => 'P1', 'address' => '10.9.0.6', 'caller-id' => 'aa:bb:cc:00:00:02'],
            ], 200),
        ]);

        $router = MikrotikRouter::create([
            'name' => 'Active Router', 'ip_address' => '10.0.0.90', 'api_port' => 8181,
            'transport' => 'rest', 'pppoe_sync_interval_minutes' => 60,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'anike', 'password' => 'reader-pass',
            'status' => 'active', 'read_only' => true,
        ]);

        // "noc" already has a real /ppp/secret imported with its own password.
        MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'routeros_id' => '*S1', 'name' => 'noc',
            'password' => 'realpass', 'service' => 'pppoe', 'profile' => 'P1',
            'disabled' => false, 'imported_at' => now(),
        ]);
        MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'routeros_id' => 'active-*OLD', 'name' => 'disconnected',
            'password' => 'shared123', 'service' => 'pppoe', 'profile' => 'P1',
            'disabled' => false, 'imported_at' => now()->subDay(),
        ]);
        MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'routeros_id' => 'active-*DUP', 'name' => 'noc',
            'password' => 'shared123', 'service' => 'pppoe', 'profile' => 'P1',
            'disabled' => false, 'imported_at' => now()->subDay(),
        ]);

        $imported = app(MikrotikImportService::class)->importActiveUsers($router, 'shared123');

        $this->assertSame(
            ['seen' => 2, 'stored' => 2, 'skipped_no_name' => 0, 'duplicate_names' => 0],
            $imported
        );

        $noc = MikrotikImportedSecret::where('mikrotik_router_id', $router->id)->where('name', 'noc')->firstOrFail();
        $this->assertSame('realpass', $noc->password, 'A real secret password must never be overwritten.');
        $this->assertNull($noc->remote_address, 'A live session IP must not overwrite the PPP secret assignment.');
        $this->assertSame('AA:BB:CC:00:00:01', $noc->device_mac);
        $this->assertSame(1, MikrotikImportedSecret::where('mikrotik_router_id', $router->id)->where('name', 'noc')->count());
        $this->assertDatabaseMissing('mikrotik_imported_secrets', [
            'mikrotik_router_id' => $router->id, 'name' => 'disconnected',
        ]);

        $walkin = MikrotikImportedSecret::where('mikrotik_router_id', $router->id)->where('name', 'walkin')->firstOrFail();
        $this->assertSame('shared123', $walkin->password);
        $this->assertSame('active-*A2', $walkin->routeros_id);
        $this->assertSame('AA:BB:CC:00:00:02', $walkin->device_mac, 'caller-id is normalised to uppercase colon form');
        $this->assertFalse((bool) $walkin->disabled);
    }

    public function test_import_active_users_reports_sessions_it_could_not_store(): void
    {
        Http::fake([
            '10.0.0.92:8181/rest/ppp/profile' => Http::response([], 200),
            '10.0.0.92:8181/rest/ppp/active' => Http::response([
                ['.id' => '*A1', 'name' => 'real-one', 'service' => 'pppoe', 'address' => '10.9.0.1'],
                ['.id' => '*A2', 'name' => '', 'address' => '10.9.0.2'],
                ['.id' => '', 'name' => 'no-id', 'address' => '10.9.0.3'],
                ['.id' => '*A4', 'name' => 'dup', 'address' => '10.9.0.4'],
                ['.id' => '*A5', 'name' => 'DUP', 'address' => '10.9.0.5'],
            ], 200),
        ]);

        $router = MikrotikRouter::create([
            'name' => 'Half Open Router', 'ip_address' => '10.0.0.92', 'api_port' => 8181,
            'transport' => 'rest', 'pppoe_sync_interval_minutes' => 60,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'anike', 'password' => 'reader-pass',
            'status' => 'active', 'read_only' => true,
        ]);

        $breakdown = app(MikrotikImportService::class)->importActiveUsers($router, 'shared123');

        $this->assertSame(
            ['seen' => 5, 'stored' => 2, 'skipped_no_name' => 2, 'duplicate_names' => 1],
            $breakdown
        );
        $this->assertSame(2, MikrotikImportedSecret::where('mikrotik_router_id', $router->id)->count());
    }

    public function test_import_active_users_endpoint_requires_a_shared_password(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Active Router', 'ip_address' => '10.0.0.91', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        $this->actingAs($user)
            ->from(route('mikrotik-routers.show', $router))
            ->post(route('mikrotik-routers.import.active-users', $router), [])
            ->assertRedirect(route('mikrotik-routers.show', $router))
            ->assertSessionHasErrors('active_password');
    }

    public function test_rest_transport_router_pushes_writes_over_the_www_service(): void
    {
        Http::fake([
            '10.0.0.78:8181/rest/ppp/profile/print' => Http::response([], 200),
            '10.0.0.78:8181/rest/ppp/profile' => Http::response(['ret' => '*7', 'name' => 'inactive'], 200),
            '10.0.0.78:8181/rest/ppp/profile/*7' => Http::response(['.id' => '*7', 'name' => 'inactive', 'rate-limit' => '1k/1k'], 200),
        ]);

        $router = MikrotikRouter::create([
            'name' => 'REST Router', 'ip_address' => '10.0.0.78', 'api_port' => 8181,
            'transport' => 'rest', 'pppoe_sync_interval_minutes' => 60,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'anike', 'password' => 'rw-pass',
            'status' => 'active', 'read_only' => false,
        ]);

        $service = app(MikrotikImportService::class);

        // add -> PUT /rest/ppp/profile
        $service->write($router, '/ppp/profile/add', ['name' => 'inactive']);
        // set -> PATCH /rest/ppp/profile/<id>
        $service->write($router, '/ppp/profile/set', ['.id' => '*7', 'rate-limit' => '1k/1k']);

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === 'http://10.0.0.78:8181/rest/ppp/profile'
            && ($request->data()['name'] ?? null) === 'inactive');
        Http::assertSent(fn ($request) => $request->method() === 'PATCH'
            && $request->url() === 'http://10.0.0.78:8181/rest/ppp/profile/*7'
            && ! array_key_exists('.id', $request->data()));
    }

    public function test_rest_client_reports_a_clear_error_when_credentials_are_rejected(): void
    {
        Http::fake(['10.0.0.79:8181/rest/*' => Http::response('unauthorized', 401)]);

        $router = MikrotikRouter::create([
            'name' => 'REST Router', 'ip_address' => '10.0.0.79', 'api_port' => 8181,
            'transport' => 'rest', 'pppoe_sync_interval_minutes' => 60,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'anike', 'password' => 'bad-pass',
            'status' => 'active', 'read_only' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("RouterOS rejected the REST user 'anike'");

        (new RouterOsRestClient)->records($router, '/ppp/secret/print');
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
            ->assertSee('name="router_api_username" value="admin"', false)
            ->assertSee('name="router_api_password"', false);

        $this->actingAs($user)->put(route('mikrotik-routers.update', $router), [
            'name' => 'Main Router', 'ip_address' => '10.0.0.3', 'api_port' => 8787,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'router_api_username' => 'admin', 'router_api_password' => '',
            'username' => 'anike10@gmail.com', 'password' => 'website-login-password',
            'status' => 'active', 'notes' => null,
        ])->assertRedirect(route('mikrotik-routers.show', $router));

        $router->refresh();
        $this->assertSame('admin', $router->username);
        $this->assertSame('router-secret', $router->password);
    }

    public function test_router_edit_requires_a_new_api_password_when_the_imported_password_cannot_be_decrypted(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Imported Router', 'ip_address' => '103.133.200.180', 'api_port' => 8787,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'admin', 'password' => 'original-secret', 'status' => 'active',
        ]);
        DB::table('mikrotik_routers')->where('id', $router->id)->update([
            'password' => 'ciphertext-created-with-another-app-key',
        ]);

        $this->actingAs($user)
            ->get(route('mikrotik-routers.edit', $router))
            ->assertOk()
            ->assertSee('The saved RouterOS API password cannot be decrypted by this local app.')
            ->assertSee('name="router_api_password" autocomplete="new-password" required', false);

        $update = [
            'name' => 'Imported Router', 'ip_address' => '103.133.200.180', 'api_port' => 8787,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'router_api_username' => 'admin', 'router_api_password' => '',
            'status' => 'active', 'notes' => null,
        ];

        $this->actingAs($user)
            ->from(route('mikrotik-routers.edit', $router))
            ->put(route('mikrotik-routers.update', $router), $update)
            ->assertRedirect(route('mikrotik-routers.edit', $router))
            ->assertSessionHasErrors('router_api_password');

        $update['router_api_password'] = 'replacement-secret';
        $this->actingAs($user)
            ->put(route('mikrotik-routers.update', $router), $update)
            ->assertRedirect(route('mikrotik-routers.show', $router))
            ->assertSessionHasNoErrors();

        $this->assertSame('replacement-secret', $router->refresh()->apiPassword());
        $this->assertFalse($router->requiresApiPasswordReentry());
    }

    public function test_customer_sync_explains_when_router_password_must_be_reentered(): void
    {
        $router = MikrotikRouter::create([
            'name' => 'Imported Router', 'ip_address' => '103.133.200.180', 'api_port' => 8787,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'admin', 'password' => 'original-secret', 'status' => 'active',
        ]);
        DB::table('mikrotik_routers')->where('id', $router->id)->update([
            'password' => 'ciphertext-created-with-another-app-key',
        ]);
        $customer = Customer::create([
            'name' => 'Imported Customer', 'phone' => '01700000305',
            'connection_id' => 'IMPORTED-305', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(MikrotikRouter::API_PASSWORD_REENTRY_MESSAGE);

        app(MikrotikCustomerSyncService::class)->sync($customer);
    }

    public function test_router_can_be_temporarily_disabled_and_enabled_from_list_action(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Temporary Router', 'ip_address' => '10.0.0.9', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        $this->actingAs($user)->patch(route('mikrotik-routers.toggle-status', $router))->assertRedirect();
        $router->refresh();
        $this->assertSame('inactive', $router->status);
        $this->assertSame('inactive', $router->last_api_status);
        $this->assertStringContainsString('temporarily disabled', $router->last_connection_message);

        $this->actingAs($user)->get(route('mikrotik-routers.index'))
            ->assertOk()
            ->assertSee('Enable');

        $this->actingAs($user)->patch(route('mikrotik-routers.toggle-status', $router))->assertRedirect();
        $router->refresh();
        $this->assertSame('active', $router->status);
        $this->assertSame('checking', $router->last_api_status);
    }

    public function test_package_profile_export_applies_selected_default_ip_pool(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Main Router', 'ip_address' => '10.0.0.10', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        $package = InternetPackage::create([
            'name' => 'Home 50', 'speed' => '50 Mbps', 'mikrotik_profile' => 'home50',
            'default_ip_pool' => 'customer-pool', 'monthly_price' => 1500, 'status' => 'active',
        ]);

        $service = $this->mock(MikrotikImportService::class);
        $service->shouldReceive('liveRecords')->twice()->andReturnUsing(fn ($givenRouter, $command) => $command === '/ppp/profile/print'
                ? [['.id' => '*P1', 'name' => 'home50']]
                : [['.id' => '*POOL1', 'name' => 'customer-pool']]);
        $service->shouldReceive('write')->once()->withArgs(fn ($givenRouter, $command, $attributes) => $givenRouter->id === $router->id
            && $command === '/ppp/profile/set'
            && $attributes['.id'] === '*P1'
            && $attributes['remote-address'] === 'customer-pool')
            ->andReturn([]);
        $service->shouldReceive('importProfiles')->once()->andReturn(1);

        $this->actingAs($user)
            ->post(route('mikrotik-routers.profiles.export', [$router, $package]))
            ->assertRedirect();
    }

    public function test_profile_export_without_app_default_keeps_router_remote_address_and_shows_errors(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Main Router', 'ip_address' => '10.0.0.11', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        $package = InternetPackage::create([
            'name' => 'Home 100', 'speed' => '100 Mbps', 'mikrotik_profile' => 'home100',
            'default_ip_pool' => null, 'monthly_price' => 2000, 'status' => 'active',
        ]);

        $service = $this->mock(MikrotikImportService::class);
        $service->shouldReceive('liveRecords')->once()->withArgs(fn ($givenRouter, $command) => $givenRouter->id === $router->id && $command === '/ppp/profile/print')
            ->andReturn([['.id' => '*P2', 'name' => 'home100', 'remote-address' => 'existing-pool']]);
        $service->shouldReceive('write')->once()->withArgs(fn ($givenRouter, $command, $attributes) => $command === '/ppp/profile/set'
            && $attributes['.id'] === '*P2'
            && $attributes['name'] === 'home100'
            && ! array_key_exists('remote-address', $attributes))
            ->andThrow(new RuntimeException('RouterOS rejected this profile value'));

        $this->actingAs($user)
            ->from(route('mikrotik-routers.compare', $router))
            ->post(route('mikrotik-routers.profiles.export', [$router, $package]))
            ->assertRedirect(route('mikrotik-routers.compare', $router))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Profile export failed on Main Router')
                && str_contains($message, 'RouterOS rejected this profile value'));
    }

    public function test_compare_delete_from_app_removes_profile_mapping_without_deleting_package(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Mapping Router', 'ip_address' => '10.0.0.12', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        $package = InternetPackage::create([
            'name' => 'Mapped Package', 'speed' => '30 Mbps', 'mikrotik_profile' => 'mapped-30',
            'default_ip_pool' => 'customer-pool', 'monthly_price' => 1200, 'status' => 'active',
        ]);

        $this->actingAs($user)->delete(route('mikrotik-routers.compare.app-item.destroy', $router, false), [
            'type' => 'profile',
            'app_id' => $package->id,
        ])->assertRedirect();

        $package->refresh();
        $this->assertNull($package->mikrotik_profile);
        $this->assertNull($package->default_ip_pool);
        $this->assertDatabaseHas('internet_packages', ['id' => $package->id, 'name' => 'Mapped Package']);
    }

    public function test_compare_delete_from_app_removes_pppoe_mapping_without_deleting_party(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Party Router', 'ip_address' => '10.0.0.13', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Preserved Party', 'phone' => '01700000001', 'connection_id' => 'party-100',
            'mikrotik_username' => 'party-100', 'mikrotik_password' => 'pppoe-password',
            'mikrotik_router_id' => $router->id, 'address' => 'Kushtia', 'status' => 'active',
            'is_customer' => true,
        ]);

        $this->actingAs($user)->delete(route('mikrotik-routers.compare.app-item.destroy', $router, false), [
            'type' => 'secret',
            'app_id' => $customer->id,
        ])->assertRedirect();

        $customer->refresh();
        $this->assertNull($customer->connection_id);
        $this->assertNull($customer->mikrotik_username);
        $this->assertNull($customer->mikrotik_password);
        $this->assertNull($customer->mikrotik_router_id);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Preserved Party']);
    }

    public function test_compare_shows_delete_from_app_in_all_three_tables_when_app_records_exist(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Compare Router', 'ip_address' => '10.0.0.14', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        InternetPackage::create([
            'name' => 'Compare Package', 'speed' => '40 Mbps', 'mikrotik_profile' => 'compare-40',
            'monthly_price' => 1400, 'status' => 'active',
        ]);
        AppIpPool::create([
            'mikrotik_router_id' => $router->id, 'name' => 'compare-pool',
            'ranges' => '10.40.0.2-10.40.0.254', 'status' => 'active',
        ]);
        Customer::create([
            'name' => 'Compare Party', 'phone' => '01700000002', 'connection_id' => 'compare-user',
            'mikrotik_username' => 'compare-user', 'mikrotik_password' => 'secret',
            'mikrotik_router_id' => $router->id, 'address' => 'Kushtia', 'status' => 'active',
            'is_customer' => true,
        ]);

        $service = $this->mock(MikrotikImportService::class);
        $service->shouldReceive('liveRecords')->once()->withArgs(fn (MikrotikRouter $givenRouter, string $command) => $givenRouter->id === $router->id && $command === '/ppp/profile/print')
            ->andReturn([['.id' => '*P1', 'name' => 'compare-40']]);
        $service->shouldReceive('liveRecords')->once()->withArgs(fn (MikrotikRouter $givenRouter, string $command) => $givenRouter->id === $router->id && $command === '/ip/pool/print')
            ->andReturn([['.id' => '*L1', 'name' => 'compare-pool', 'ranges' => '10.40.0.2-10.40.0.254']]);
        $service->shouldReceive('liveRecords')->once()->withArgs(fn (MikrotikRouter $givenRouter, string $command) => $givenRouter->id === $router->id && $command === '/ppp/secret/print')
            ->andReturn([['.id' => '*S1', 'name' => 'compare-user']]);

        $response = $this->actingAs($user)->get(route('mikrotik-routers.compare', $router, false));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertSame(3, substr_count($html, '>Delete from App</button>'));
        $this->assertSame(3, substr_count($html, '<th>App Actions</th><th>Where it exists</th><th>MikroTik Actions</th>'));
        preg_match_all('/<table>.*?<\/table>/s', $html, $tables);
        $this->assertCount(3, $tables[0]);
        foreach ($tables[0] as $table) {
            $this->assertLessThan(strpos($table, 'App + MikroTik'), strpos($table, '>Delete from App</button>'));
            $this->assertLessThan(strpos($table, '>Delete from MikroTik</button>'), strpos($table, 'App + MikroTik'));
        }
    }
}
