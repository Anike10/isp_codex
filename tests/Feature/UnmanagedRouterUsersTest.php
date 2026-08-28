<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertSame(0, app(\App\Services\MikrotikImportService::class)->unmanagedSecrets()->count());
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
        $this->assertSame(0, app(\App\Services\MikrotikImportService::class)->unmanagedSecrets()->count());
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
