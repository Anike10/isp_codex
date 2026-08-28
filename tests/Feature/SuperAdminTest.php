<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_passes_every_permission_and_menu_check_without_grants(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->hasPermission('manage_payment_accounts'));
        $this->assertTrue($superAdmin->hasPermission('a_permission_that_does_not_exist'));
        $this->assertTrue($superAdmin->canAccessMenu('payment_accounts'));

        $this->actingAs($superAdmin)->get(route('payment-accounts.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('users.index'))->assertOk();
    }

    public function test_explicit_denial_does_not_apply_to_a_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $superAdmin->deniedPermissions()->attach(Permission::where('name', 'view_dashboard')->firstOrFail());

        $this->assertTrue($superAdmin->fresh()->hasPermission('view_dashboard'));
    }

    public function test_only_a_super_admin_can_grant_super_admin(): void
    {
        $manager = User::factory()->create();
        $manager->permissions()->attach(Permission::where('name', 'manage_users')->firstOrFail());
        $target = User::factory()->create();

        $this->actingAs($manager)
            ->patch(route('users.super-admin.update', $target), ['is_super_admin' => 1])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->is_super_admin);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('users.super-admin.update', $target), ['is_super_admin' => 1])
            ->assertRedirect();

        $this->assertTrue($target->fresh()->is_super_admin);

        $this->actingAs($superAdmin)
            ->patch(route('users.super-admin.update', $target), ['is_super_admin' => 0])
            ->assertRedirect();

        $this->assertFalse($target->fresh()->is_super_admin);
    }

    public function test_the_last_super_admin_cannot_be_demoted(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('users.super-admin.update', $superAdmin), ['is_super_admin' => 0])
            ->assertSessionHasErrors('is_super_admin');

        $this->assertTrue($superAdmin->fresh()->is_super_admin);

        // With a second super admin present, the first can be demoted.
        $second = User::factory()->superAdmin()->create();

        $this->actingAs($second)
            ->patch(route('users.super-admin.update', $superAdmin), ['is_super_admin' => 0])
            ->assertRedirect();

        $this->assertFalse($superAdmin->fresh()->is_super_admin);
    }

    public function test_standard_user_manager_cannot_edit_or_delete_a_super_admin(): void
    {
        $manager = User::factory()->create();
        $manager->permissions()->attach(Permission::where('name', 'manage_users')->firstOrFail());
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($manager)->get(route('users.edit', $superAdmin))->assertForbidden();
        $this->actingAs($manager)->put(route('users.update', $superAdmin), [
            'name' => 'Compromised',
            'email' => $superAdmin->email,
        ])->assertForbidden();
        $this->actingAs($manager)->delete(route('users.destroy', $superAdmin))->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
            'name' => $superAdmin->name,
            'is_super_admin' => true,
        ]);
    }

    public function test_artisan_command_grants_and_revokes_super_admin(): void
    {
        $user = User::factory()->create(['email' => 'ops@example.test']);

        $this->artisan('user:super-admin', ['email' => 'ops@example.test'])
            ->assertExitCode(0);
        $this->assertTrue($user->fresh()->is_super_admin);

        // The last super admin is protected from the command too.
        $this->artisan('user:super-admin', ['email' => 'ops@example.test', '--revoke' => true])
            ->assertExitCode(1);
        $this->assertTrue($user->fresh()->is_super_admin);

        User::factory()->superAdmin()->create();
        $this->artisan('user:super-admin', ['email' => 'ops@example.test', '--revoke' => true])
            ->assertExitCode(0);
        $this->assertFalse($user->fresh()->is_super_admin);

        $this->artisan('user:super-admin', ['email' => 'nobody@example.test'])
            ->assertExitCode(1);
    }

    public function test_users_index_shows_the_toggle_only_to_a_super_admin(): void
    {
        $target = User::factory()->create(['name' => 'Plain Manager']);

        $manager = User::factory()->create();
        $manager->permissions()->attach(Permission::where('name', 'manage_users')->firstOrFail());

        $this->actingAs($manager)->get(route('users.index'))
            ->assertOk()
            ->assertSee('Access level')
            ->assertDontSee('Make super admin');

        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Root']);

        $this->actingAs($superAdmin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('Make super admin');
    }
}
