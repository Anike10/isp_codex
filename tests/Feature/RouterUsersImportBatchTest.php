<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouterUsersImportBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_importing_two_router_users_with_the_same_name_does_not_error(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'view_unmanaged_router_users')->firstOrFail());
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $routerA = MikrotikRouter::create([
            'name' => 'Router A', 'ip_address' => '10.20.0.1', 'api_port' => 8728,
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        $routerB = MikrotikRouter::create([
            'name' => 'Router B', 'ip_address' => '10.20.0.2', 'api_port' => 8728,
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        $s1 = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $routerA->id, 'routeros_id' => '*A1', 'name' => 'DUP-USER',
            'password' => 'p1', 'service' => 'pppoe', 'profile' => '10M', 'disabled' => false, 'imported_at' => now(),
        ]);
        $s2 = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $routerB->id, 'routeros_id' => '*B1', 'name' => 'DUP-USER',
            'password' => 'p2', 'service' => 'pppoe', 'profile' => '10M', 'disabled' => false, 'imported_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('router-users.import'), [
            'secret_ids' => [$s1->id, $s2->id],
        ]);

        $response->assertRedirect(route('router-users.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Customer::where('connection_id', 'DUP-USER')->count());
        $this->assertDatabaseHas('mikrotik_imported_secrets', ['id' => $s1->id, 'customer_id' => Customer::where('connection_id', 'DUP-USER')->value('id')]);
        $this->assertDatabaseHas('mikrotik_imported_secrets', ['id' => $s2->id, 'customer_id' => Customer::where('connection_id', 'DUP-USER')->value('id')]);
    }

    public function test_importing_a_name_held_by_a_soft_deleted_party_does_not_error(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'view_unmanaged_router_users')->firstOrFail());
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $router = MikrotikRouter::create([
            'name' => 'Router C', 'ip_address' => '10.20.0.3', 'api_port' => 8728,
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        // A deleted party still holds the unique connection_id / mikrotik_username,
        // and the router-users list still shows this PPPoE user as "not in app".
        $deleted = Customer::create([
            'name' => 'Deleted Party', 'phone' => '017', 'address' => 'x', 'status' => 'active',
            'connection_id' => 'GHOST-1', 'mikrotik_username' => 'GHOST-1',
        ]);
        $deleted->delete();

        $secret = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'routeros_id' => '*C1', 'name' => 'GHOST-1',
            'password' => 'p', 'service' => 'pppoe', 'profile' => '10M', 'disabled' => false, 'imported_at' => now(),
        ]);

        // No 500, and the deleted party is brought back and re-linked.
        $this->actingAs($user)->post(route('router-users.import'), [
            'secret_ids' => [$secret->id],
        ])->assertRedirect(route('router-users.index'))->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted('customers', ['id' => $deleted->id]);
        $this->assertSame(1, Customer::where('connection_id', 'GHOST-1')->count());
        $this->assertDatabaseHas('mikrotik_imported_secrets', ['id' => $secret->id, 'customer_id' => $deleted->id]);
        $this->assertStringContainsString('router users: 0 created, 1 updated', session('success'));
    }
}
