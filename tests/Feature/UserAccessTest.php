<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_form_shows_hierarchical_menu_access_and_mobile_submit_action(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Direct Permissions')
            ->assertSee('Menu &amp; submenu access is exact for this user.', false)
            ->assertSee('Network')
            ->assertSee('MikroTik &amp; OLT Tools', false)
            ->assertSee('Menu &amp; Submenu Access', false)
            ->assertSee('Create Invoice')
            ->assertSee('data-mobile-submit', false)
            ->assertSee('Save User');
    }

    public function test_explicit_user_denial_overrides_direct_and_role_permissions(): void
    {
        $permission = Permission::where('name', 'view_dashboard')->firstOrFail();
        $role = Role::create(['name' => 'dashboard-role', 'label' => 'Dashboard Role']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);
        $user->permissions()->attach($permission);

        $this->assertTrue($user->fresh()->hasPermission('view_dashboard'));

        $user->deniedPermissions()->attach($permission);

        $this->assertFalse($user->fresh()->hasPermission('view_dashboard'));
    }

    public function test_update_persists_an_exact_access_list_even_when_role_grants_more(): void
    {
        $manager = $this->manager();
        $dashboard = Permission::where('name', 'view_dashboard')->firstOrFail();
        $customers = Permission::where('name', 'manage_customers')->firstOrFail();
        $role = Role::create(['name' => 'billing-role', 'label' => 'Billing Role']);
        $role->permissions()->attach([$dashboard->id, $customers->id]);

        $target = User::factory()->create([
            'name' => 'Menu Access Target',
            'email' => 'menu-access-target@example.test',
        ]);
        $target->roles()->attach($role);

        $this->actingAs($manager)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => [$role->id],
            'permissions' => [],
            'menu_access_present' => '1',
            'menus' => ['parties'],
        ])->assertRedirect(route('users.index'));

        $target = $target->fresh();

        $this->assertTrue($target->hasPermission('manage_customers'));
        $this->assertFalse($target->hasPermission('view_dashboard'));
        $this->assertTrue($target->permissions()->whereKey($customers->id)->exists());
        $this->assertTrue($target->deniedPermissions()->whereKey($dashboard->id)->exists());
        $this->assertTrue($target->menuAccesses()->where('menu_key', 'parties')->where('allowed', true)->exists());
        $this->assertTrue($target->menuAccesses()->where('menu_key', 'invoices')->where('allowed', false)->exists());
    }

    public function test_an_unchecked_submenu_is_hidden_and_blocked_without_disabling_its_sibling(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $user->menuAccesses()->createMany([
            ['menu_key' => 'invoices', 'allowed' => false],
            ['menu_key' => 'quotations', 'allowed' => true],
        ]);

        $this->actingAs($user)->get(route('invoices.index'))->assertForbidden();
        $this->actingAs($user)->get(route('quotations.index'))->assertOk();
    }

    private function manager(): User
    {
        $manager = User::factory()->create();
        $manager->permissions()->attach(Permission::where('name', 'manage_users')->firstOrFail());

        return $manager;
    }
}
