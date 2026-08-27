<?php

namespace Tests\Feature;

use App\Models\AppIpPool;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedProfile;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\RecordVersion;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MikrotikImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_can_be_edited_and_records_history(): void
    {
        $user = User::factory()->create(['name' => 'Package Editor']);
        $user->permissions()->attach(Permission::where('name', 'manage_packages')->firstOrFail());
        $package = InternetPackage::create([
            'name' => 'Home 20',
            'speed' => '20 Mbps',
            'mikrotik_profile' => 'home20',
            'monthly_price' => 1000,
            'description' => 'Old package',
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('packages.edit', $package))
            ->assertOk()
            ->assertSee('Edit Package')
            ->assertSee('Home 20')
            ->assertSee('home20');

        $this->actingAs($user)->put(route('packages.update', $package), [
            'name' => 'Home 25',
            'speed' => '25 Mbps',
            'mikrotik_profile' => '',
            'monthly_price' => 1200,
            'description' => 'Updated package',
            'status' => 'inactive',
        ])->assertRedirect(route('packages.show', $package));

        $package->refresh();
        $this->assertSame('Home 25', $package->name);
        $this->assertSame('Home 25', $package->mikrotik_profile);
        $this->assertSame('inactive', $package->status);

        $version = RecordVersion::where('versionable_type', InternetPackage::class)
            ->where('versionable_id', $package->id)
            ->firstOrFail();

        $this->assertSame('Package Editor', $version->edited_by_name);
        $this->assertSame('Home 20', $version->old_values['name']);
        $this->assertSame('Home 25', $version->new_values['name']);
        $this->assertContains('monthly_price', $version->changed_fields);

        $this->actingAs($user)->get(route('packages.show', $package))
            ->assertOk()
            ->assertSee('Edit History')
            ->assertSee('View Old Version')
            ->assertDontSee('History Copy')
            ->assertSee('Home 20');
    }

    public function test_package_edit_shows_selected_default_and_running_router_pool(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_packages')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Main Router', 'ip_address' => '10.10.10.1', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        AppIpPool::create([
            'mikrotik_router_id' => $router->id,
            'name' => 'home-pool',
            'ranges' => '10.20.0.2-10.20.0.254',
            'status' => 'active',
        ]);
        $package = InternetPackage::create([
            'name' => 'Home 20', 'speed' => '20 Mbps', 'mikrotik_profile' => 'home20',
            'default_ip_pool' => 'home-pool', 'monthly_price' => 1000, 'status' => 'active',
        ]);
        MikrotikImportedProfile::create([
            'mikrotik_router_id' => $router->id, 'routeros_id' => '*1', 'name' => 'home20',
            'remote_address' => 'home-pool', 'disabled' => false, 'imported_at' => now(),
        ]);

        $service = $this->mock(MikrotikImportService::class);
        $service->shouldReceive('liveRecords')->once()->withArgs(fn ($givenRouter, $command) =>
            $givenRouter->id === $router->id && $command === '/ppp/profile/print')
            ->andReturn([['.id' => '*1', 'name' => 'home20', 'remote-address' => 'home-pool']]);

        $this->actingAs($user)->get(route('packages.edit', $package))
            ->assertOk()
            ->assertSee('Default IP Pool')
            ->assertSee('value="home-pool" selected', false)
            ->assertSee('Main Router')
            ->assertSee('Live RouterOS')
            ->assertSee('Selected default');

        $this->actingAs($user)->put(route('packages.update', $package), [
            'name' => 'Home 20', 'speed' => '20 Mbps', 'mikrotik_profile' => 'home20',
            'default_ip_pool' => '', 'monthly_price' => 1000, 'description' => null, 'status' => 'active',
        ])->assertRedirect(route('packages.show', $package));

        $this->assertNull($package->fresh()->default_ip_pool);
    }

    public function test_package_index_shows_serial_number_and_ip_pool_column(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_packages')->firstOrFail());
        AppIpPool::create([
            'name' => 'business-pool',
            'ranges' => '10.30.0.2-10.30.0.254',
            'status' => 'active',
        ]);
        InternetPackage::create([
            'name' => 'Business 50', 'speed' => '50 Mbps', 'mikrotik_profile' => 'business50',
            'default_ip_pool' => 'business-pool', 'monthly_price' => 2500, 'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('packages.index'))
            ->assertOk()
            ->assertSee('<th>SL</th>', false)
            ->assertSee('<th>IP Pool</th>', false)
            ->assertSee('<td>1</td>', false)
            ->assertSee('business-pool')
            ->assertSee('class="package-ip-pool-select"', false)
            ->assertSee('value="business-pool" selected', false);
    }

    public function test_package_ip_pool_can_be_changed_inline_from_the_index(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_packages')->firstOrFail());
        AppIpPool::create(['name' => 'home-pool', 'ranges' => '10.20.0.2-10.20.0.254', 'status' => 'active']);
        AppIpPool::create(['name' => 'business-pool', 'ranges' => '10.30.0.2-10.30.0.254', 'status' => 'active']);
        $package = InternetPackage::create([
            'name' => 'Inline Pool Plan', 'speed' => '30 Mbps', 'mikrotik_profile' => 'inline30',
            'default_ip_pool' => 'home-pool', 'monthly_price' => 1000, 'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Inline Pool Customer',
            'phone' => '01700000009',
            'connection_id' => 'inline-pool-customer',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $customer->update([
            'learned_ip_address' => '10.20.0.25',
            'learned_ip_package_id' => $package->id,
            'last_connected_ip' => '10.20.0.25',
            'last_connected_at' => now(),
        ]);

        $this->actingAs($user)->patchJson(route('packages.inline-update', $package), [
            'field' => 'default_ip_pool',
            'value' => 'business-pool',
        ])->assertOk()->assertJson([
            'message' => 'Package updated.',
            'value' => 'business-pool',
        ]);
        $this->assertSame('business-pool', $package->fresh()->default_ip_pool);
        $customer->refresh();
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->learned_ip_package_id);
        $this->assertNull($customer->last_connected_ip);
        $this->assertNull($customer->last_connected_at);

        $this->actingAs($user)->patchJson(route('packages.inline-update', $package), [
            'field' => 'default_ip_pool',
            'value' => 'missing-pool',
        ])->assertUnprocessable();
        $this->assertSame('business-pool', $package->fresh()->default_ip_pool);

        $this->actingAs($user)->patchJson(route('packages.inline-update', $package), [
            'field' => 'default_ip_pool',
            'value' => '',
        ])->assertOk()->assertJson(['value' => null]);
        $this->assertNull($package->fresh()->default_ip_pool);
    }

    public function test_assigned_package_errors_include_name_and_bulk_force_delete_moves_subscriptions(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_packages')->firstOrFail());
        $assigned = InternetPackage::create([
            'name' => 'Assigned Legacy', 'speed' => '10 Mbps', 'mikrotik_profile' => 'legacy10',
            'monthly_price' => 500, 'status' => 'active',
        ]);
        $unassigned = InternetPackage::create([
            'name' => 'Unused Legacy', 'speed' => '15 Mbps', 'mikrotik_profile' => 'legacy15',
            'monthly_price' => 700, 'status' => 'active',
        ]);
        $replacement = InternetPackage::create([
            'name' => 'Replacement 20', 'speed' => '20 Mbps', 'mikrotik_profile' => 'replacement20',
            'monthly_price' => 900, 'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Package Customer', 'phone' => '01700000001', 'connection_id' => 'pkg-customer-1',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false,
        ]);
        $subscription = Subscription::create([
            'customer_id' => $customer->id, 'internet_package_id' => $assigned->id,
            'start_date' => now()->toDateString(), 'status' => 'active',
        ]);
        $customer->update([
            'learned_ip_address' => '10.70.0.20',
            'learned_ip_package_id' => $assigned->id,
            'last_connected_ip' => '10.70.0.20',
            'last_connected_at' => now(),
        ]);

        $this->actingAs($user)->delete(route('packages.destroy', $assigned))
            ->assertSessionHas('error', fn ($message) => str_contains($message, "Package 'Assigned Legacy'"));

        $this->actingAs($user)->delete(route('packages.bulk-destroy'), [
            'package_ids' => [$assigned->id, $unassigned->id],
        ])->assertRedirect(route('packages.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Assigned Legacy'));

        $this->assertDatabaseHas('internet_packages', ['id' => $assigned->id]);
        $this->assertDatabaseMissing('internet_packages', ['id' => $unassigned->id]);

        $this->actingAs($user)->delete(route('packages.bulk-destroy'), [
            'package_ids' => [$assigned->id],
            'force_delete' => '1',
            'replacement_package_id' => $replacement->id,
        ])->assertRedirect(route('packages.index'))
            ->assertSessionHas('success', fn ($message) =>
                str_contains($message, 'Replacement 20') && str_contains($message, '1 subscription'));

        $this->assertDatabaseMissing('internet_packages', ['id' => $assigned->id]);
        $this->assertSame($replacement->id, $subscription->fresh()->internet_package_id);
        $customer->refresh();
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->learned_ip_package_id);
        $this->assertNull($customer->last_connected_ip);
        $this->assertNull($customer->last_connected_at);
    }
}
