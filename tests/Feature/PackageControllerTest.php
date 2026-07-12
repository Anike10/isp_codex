<?php

namespace Tests\Feature;

use App\Models\InternetPackage;
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
}
