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
        $this->makeSecret('lonely-user', 'home-10');

        $plain = $this->user(['view_dashboard']);
        $this->actingAs($plain)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Router users not in the app')
            ->assertDontSee('lonely-user');

        $seer = $this->user(['view_dashboard', 'view_unmanaged_router_users']);
        $this->actingAs($seer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Router users not in the app')
            ->assertSee('lonely-user');
    }

    public function test_a_secret_that_matches_a_party_username_is_not_listed(): void
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
            ->assertDontSee('known-user')
            ->assertSee('Every router PPPoE user is linked to a party');

        $this->assertSame(0, app(MikrotikImportService::class)->unmanagedSecrets()->count());
        $this->assertSame($secret->id, $secret->fresh()->id);
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
                    'profile' => 'home-10', 'address' => '10.7.0.9'],
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

        $this->actingAs($seer)->get(route('router-users.index'))
            ->assertOk()
            ->assertSee('connected-only')
            ->assertSee('10.7.0.9');
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

    private function makeSecret(string $name, string $profile, bool $disabled = false): MikrotikImportedSecret
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
            'disabled' => $disabled,
            'imported_at' => now(),
        ]);
    }
}
