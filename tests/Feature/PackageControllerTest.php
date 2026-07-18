<?php

namespace Tests\Feature;

use App\Models\AppIpPool;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedProfile;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\RecordVersion;
use App\Models\User;
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

        $this->actingAs($user)->get(route('packages.edit', $package))
            ->assertOk()
            ->assertSee('Default IP Pool')
            ->assertSee('value="home-pool" selected', false)
            ->assertSee('Main Router')
            ->assertSee('Selected default');

        $this->actingAs($user)->put(route('packages.update', $package), [
            'name' => 'Home 20', 'speed' => '20 Mbps', 'mikrotik_profile' => 'home20',
            'default_ip_pool' => '', 'monthly_price' => 1000, 'description' => null, 'status' => 'active',
        ])->assertRedirect(route('packages.show', $package));

        $this->assertNull($package->fresh()->default_ip_pool);
    }
}
