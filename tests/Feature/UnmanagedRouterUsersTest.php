<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\User;
use App\Services\MikrotikImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnmanagedRouterUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_router_secrets_missing_from_the_app_only_with_permission(): void
    {
        $this->makeSecret('lonely-user', 'home-10', deviceMac: '00:8D:FF:02:2A:17');

        $plain = $this->user(['view_dashboard']);
        $this->actingAs($plain)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Router users not in the app')
            ->assertDontSee('lonely-user');

        $seer = $this->user(['view_dashboard', 'view_unmanaged_router_users']);
        $this->actingAs($seer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Router users not in the app')
            ->assertSee('lonely-user')
            ->assertSee('<th>Device MAC</th>', false)
            ->assertSee('Status at last refresh')
            ->assertSee('00:8D:FF:02:2A:17');
    }

    public function test_a_secret_that_matches_a_party_username_is_shown_but_not_selectable(): void
    {
        $secret = $this->makeSecret('known-user', 'home-10');
        Customer::create([
            'name' => 'Known', 'phone' => '01700000000',
            'connection_id' => 'other-id', 'mikrotik_username' => 'KNOWN-USER',
            'address' => 'Kushtia', 'status' => 'active',
        ]);

        $seer = $this->user(['view_dashboard', 'view_unmanaged_router_users']);
        $this->actingAs($seer)->get(route('router-users.index'))
            ->assertOk()
            ->assertSee('known-user')
            ->assertSee('Name match')
            ->assertSee('Known')
            ->assertDontSee('name="secret_ids[]" value="'.$secret->id.'"', false);

        $this->assertSame(0, app(MikrotikImportService::class)->unmanagedSecrets()->count());

        $overview = app(MikrotikImportService::class)->importedSecretsOverview();
        $this->assertFalse($overview->firstWhere('id', $secret->id)->is_unmanaged);
        $this->assertSame('Known', $overview->firstWhere('id', $secret->id)->matched_customer->name);
    }

    public function test_index_marks_linked_rows_and_filters_by_router(): void
    {
        $routerA = MikrotikRouter::create([
            'name' => 'Router A', 'ip_address' => '10.1.0.1', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'inactive',
        ]);
        $routerB = MikrotikRouter::create([
            'name' => 'Router B', 'ip_address' => '10.2.0.1', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'inactive',
        ]);

        $party = Customer::create([
            'name' => 'Linked Party', 'phone' => '01700000001', 'connection_id' => 'a-linked',
            'mikrotik_username' => 'a-linked', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $linked = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $routerA->id, 'customer_id' => $party->id, 'routeros_id' => '*A1',
            'name' => 'a-linked', 'password' => 'x', 'service' => 'pppoe', 'profile' => 'home-10',
            'disabled' => false, 'imported_at' => now(),
        ]);
        $loose = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $routerA->id, 'routeros_id' => '*A2',
            'name' => 'a-loose', 'password' => 'x', 'service' => 'pppoe', 'profile' => 'home-10',
            'disabled' => false, 'imported_at' => now(),
        ]);
        $other = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $routerB->id, 'routeros_id' => '*B1',
            'name' => 'b-user', 'password' => 'x', 'service' => 'pppoe', 'profile' => 'home-10',
            'disabled' => false, 'imported_at' => now(),
        ]);

        $seer = $this->user(['view_dashboard', 'view_unmanaged_router_users']);

        // No filter: every router user across both routers is shown.
        $this->actingAs($seer)->get(route('router-users.index'))
            ->assertOk()
            ->assertSee('Linked')
            ->assertSee('a-linked')
            ->assertSee('a-loose')
            ->assertSee('b-user')
            ->assertSee('3 imported router user(s)');

        // Filtered to Router A: Router B's user disappears.
        $this->actingAs($seer)->get(route('router-users.index', ['router' => $routerA->id]))
            ->assertOk()
            ->assertSee('a-linked')
            ->assertSee('a-loose')
            ->assertDontSee('b-user')
            ->assertSee('on Router A');

        // Only the loose secret is selectable for import.
        $this->actingAs($seer)->get(route('router-users.index', ['router' => $routerA->id]))
            ->assertDontSee('name="secret_ids[]" value="'.$linked->id.'"', false)
            ->assertSee('name="secret_ids[]" value="'.$loose->id.'"', false);
    }

    public function test_index_shows_the_linked_party_device_mac(): void
    {
        $router = MikrotikRouter::create([
            'name' => 'Router M', 'ip_address' => '10.3.0.1', 'api_port' => 8728,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'api', 'password' => 'secret', 'status' => 'inactive',
        ]);
        $party = Customer::create([
            'name' => 'MAC Party', 'phone' => '01700000002', 'connection_id' => 'm-user',
            'mikrotik_username' => 'm-user', 'last_connected_mac' => '00:8D:FF:02:2A:17',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'customer_id' => $party->id, 'routeros_id' => '*M1',
            'name' => 'm-user', 'password' => 'x', 'service' => 'pppoe', 'disabled' => false, 'imported_at' => now(),
        ]);

        $this->actingAs($this->user(['view_dashboard', 'view_unmanaged_router_users']))
            ->get(route('router-users.index'))
            ->assertOk()
            ->assertSee('Device MAC')
            ->assertSee('00:8D:FF:02:2A:17');
    }

    public function test_import_creates_parties_from_selected_unmanaged_secrets(): void
    {
        $secretA = $this->makeSecret('router-user-a', 'home-10');
        $secretB = $this->makeSecret('router-user-b', 'home-20', disabled: true);

        $seer = $this->user(['view_dashboard', 'view_unmanaged_router_users']);

        $this->actingAs($seer)->post(route('router-users.import'), [
            'secret_ids' => [$secretA->id, $secretB->id],
        ])->assertRedirect(route('router-users.index'));

        $a = Customer::where('connection_id', 'router-user-a')->first();
        $b = Customer::where('connection_id', 'router-user-b')->first();

        $this->assertNotNull($a);
        $this->assertSame('router-user-a', $a->mikrotik_username);
        $this->assertSame('active', $a->status);
        $this->assertSame($a->id, $secretA->fresh()->customer_id);
        $this->assertTrue($a->subscriptions()->where('status', 'active')->exists());

        $this->assertNotNull($b);
        $this->assertSame('inactive', $b->status);

        // Both are now managed, so nothing remains unlisted.
        $this->assertSame(0, app(MikrotikImportService::class)->unmanagedSecrets()->count());
    }

    public function test_import_as_special_customer_forces_active_status(): void
    {
        $secret = $this->makeSecret('special-router-user', 'home-10', disabled: true);
        $seer = $this->user(['view_dashboard', 'view_unmanaged_router_users']);

        $this->actingAs($seer)->post(route('router-users.import'), [
            'secret_ids' => [$secret->id],
            'never_suspend' => '1',
        ])->assertRedirect();

        $customer = Customer::where('connection_id', 'special-router-user')->firstOrFail();
        $this->assertTrue((bool) $customer->never_suspend);
        $this->assertSame('active', $customer->status);
    }

    public function test_pull_active_connections_lists_connected_users_missing_from_the_app(): void
    {
        Http::fake([
            '10.0.0.7:8181/rest/ppp/profile' => Http::response([
                ['.id' => '*1', 'name' => 'home-10', 'disabled' => 'false'],
            ], 200),
            '10.0.0.7:8181/rest/ppp/active' => Http::response([
                ['.id' => '*A9', 'name' => 'connected-only', 'service' => 'pppoe',
                    'profile' => 'home-10', 'address' => '10.7.0.9', 'caller-id' => '00:8d:ff:02:2a:17'],
            ], 200),
        ]);

        // Only the REST fake router should be polled — otherwise the loop tries
        // to open a real binary-API socket to any other seeded active router.
        MikrotikRouter::query()->update(['status' => 'inactive']);

        MikrotikRouter::create([
            'name' => 'Edge Router', 'ip_address' => '10.0.0.7', 'api_port' => 8181,
            'transport' => 'rest', 'pppoe_sync_interval_minutes' => 10,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'anike', 'password' => 'reader-pass',
            'status' => 'active', 'read_only' => true,
        ]);

        $seer = $this->user(['view_dashboard', 'view_unmanaged_router_users']);

        $this->actingAs($seer)->post(route('router-users.refresh-active'), [
            'active_password' => 'shared-pw',
        ])->assertRedirect(route('router-users.index'));

        $secret = MikrotikImportedSecret::where('name', 'connected-only')->firstOrFail();
        $this->assertSame('shared-pw', $secret->password);
        $this->assertSame('active-*A9', $secret->routeros_id);
        $this->assertSame('00:8D:FF:02:2A:17', $secret->device_mac);

        // An unmanaged (no party) row still shows its live device MAC.
        $this->actingAs($seer)->get(route('router-users.index'))
            ->assertOk()
            ->assertSee('connected-only')
            ->assertSee('10.7.0.9')
            ->assertSee('active session')
            ->assertSee('00:8D:FF:02:2A:17');
    }

    public function test_refresh_active_requires_a_shared_password(): void
    {
        $seer = $this->user(['view_dashboard', 'view_unmanaged_router_users']);

        $this->actingAs($seer)
            ->from(route('router-users.index'))
            ->post(route('router-users.refresh-active'), [])
            ->assertRedirect(route('router-users.index'))
            ->assertSessionHasErrors('active_password');
    }

    public function test_refresh_active_endpoint_requires_the_permission(): void
    {
        $plain = $this->user(['view_dashboard']);

        $this->actingAs($plain)
            ->post(route('router-users.refresh-active'), ['active_password' => 'x'])
            ->assertForbidden();
    }

    public function test_import_endpoint_requires_the_permission(): void
    {
        $secret = $this->makeSecret('blocked-user', 'home-10');
        $plain = $this->user(['view_dashboard']);

        $this->actingAs($plain)->post(route('router-users.import'), ['secret_ids' => [$secret->id]])
            ->assertForbidden();

        $this->assertNull(Customer::where('connection_id', 'blocked-user')->first());
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $name) {
            $user->permissions()->attach(Permission::where('name', $name)->firstOrFail());
        }

        return $user->fresh();
    }

    private function makeSecret(string $name, string $profile, bool $disabled = false, ?string $deviceMac = null): MikrotikImportedSecret
    {
        static $routerOsSeq = 0;
        $routerOsSeq++;

        $router = MikrotikRouter::firstOrCreate(
            ['ip_address' => '10.0.0.1'],
            [
                'name' => 'Core Router', 'api_port' => 8728, 'pppoe_sync_interval_minutes' => 10,
                'inactive_pppoe_profile' => 'inactive', 'username' => 'api', 'password' => 'secret', 'status' => 'active',
            ]
        );

        return MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id,
            'routeros_id' => '*'.$routerOsSeq,
            'name' => $name,
            'password' => 'pw-'.$name,
            'service' => 'pppoe',
            'profile' => $profile,
            'device_mac' => $deviceMac,
            'disabled' => $disabled,
            'imported_at' => now(),
        ]);
    }
}
