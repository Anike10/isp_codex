<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Organization;
use App\Models\OltProtocolProfile;
use App\Models\PaymentAccount;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementCrudActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_pages_render_the_new_edit_and_delete_actions(): void
    {
        $manager = $this->manager(['manage_users', 'manage_products', 'manage_payment_accounts', 'manage_invoices', 'manage_mikrotik_routers']);
        $target = User::factory()->create();
        $role = Role::create(['name' => 'page-role', 'label' => 'Page Role']);
        $category = ProductCategory::create(['name' => 'Page Category']);
        $warehouse = Warehouse::create(['name' => 'Page Warehouse', 'code' => 'PAGE', 'is_active' => true, 'is_default' => false]);
        $account = PaymentAccount::create(['payment_method' => 'bank', 'account_name' => 'Page Account', 'account_number' => 'PAGE-001', 'opening_balance' => 0, 'status' => 'active']);
        $organization = Organization::create(['name' => 'Page Organization', 'is_default' => false, 'is_active' => true]);
        $profile = OltProtocolProfile::create(['key' => 'page_profile', 'label' => 'Page Profile', 'pon_interface_command' => 'interface epon {pon_port}']);

        $this->actingAs($manager)->get(route('users.index'))->assertOk()->assertSee(route('users.destroy', $target), false);
        $this->actingAs($manager)->get(route('roles.index'))->assertOk()->assertSee(route('roles.destroy', $role), false);
        $this->actingAs($manager)->get(route('product-categories.index'))->assertOk()->assertSee(route('product-categories.destroy', $category), false);
        $this->actingAs($manager)->get(route('warehouses.index'))->assertOk()->assertSee(route('warehouses.edit', $warehouse), false)->assertSee(route('warehouses.destroy', $warehouse), false);
        $this->actingAs($manager)->get(route('payment-accounts.index'))->assertOk()->assertSee(route('payment-accounts.edit', $account), false)->assertSee(route('payment-accounts.destroy', $account), false);
        $this->actingAs($manager)->get(route('organizations.index'))->assertOk()->assertSee(route('organizations.destroy', $organization), false);
        $this->actingAs($manager)->get(route('olt-onus.protocol-profiles.index'))->assertOk()->assertSee(route('olt-onus.protocol-profiles.destroy', $profile), false);
    }

    public function test_user_delete_is_available_but_self_delete_is_blocked(): void
    {
        $manager = $this->manager(['manage_users']);
        $target = User::factory()->create();

        $this->actingAs($manager)->get(route('users.index'))
            ->assertOk()
            ->assertSee(route('users.destroy', $target), false)
            ->assertSee('Delete');

        $this->actingAs($manager)->delete(route('users.destroy', $manager))
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $manager->id]);

        $this->actingAs($manager)->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_roles_and_empty_categories_can_be_deleted_with_dependency_guards(): void
    {
        $manager = $this->manager(['manage_users', 'manage_products']);
        $role = Role::create(['name' => 'temporary-role', 'label' => 'Temporary Role']);

        $this->actingAs($manager)->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $this->actingAs($manager)->delete(route('roles.destroy', $adminRole))
            ->assertSessionHasErrors('role');

        $category = ProductCategory::create(['name' => 'Temporary Category']);
        $this->actingAs($manager)->put(route('product-categories.update', $category), [
            'name' => 'Renamed Category',
            'parent_id' => null,
        ])->assertRedirect(route('product-categories.index'));

        $this->actingAs($manager)->delete(route('product-categories.destroy', $category))
            ->assertRedirect(route('product-categories.index'));

        $usedCategory = ProductCategory::create(['name' => 'Used Category']);
        Product::create([
            'name' => 'Category Product',
            'sku' => 'CATEGORY-001',
            'product_category_id' => $usedCategory->id,
            'purchase_price' => 0,
            'sale_price' => 0,
            'stock_quantity' => 0,
        ]);
        $this->actingAs($manager)->delete(route('product-categories.destroy', $usedCategory))
            ->assertSessionHasErrors('category');
    }

    public function test_empty_warehouse_can_be_edited_and_deleted_but_default_is_protected(): void
    {
        $manager = $this->manager(['manage_products']);
        $warehouse = Warehouse::create([
            'name' => 'Temporary Warehouse',
            'code' => 'TEMP',
            'address' => null,
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($manager)->get(route('warehouses.edit', $warehouse))->assertOk();
        $this->actingAs($manager)->put(route('warehouses.update', $warehouse), [
            'name' => 'Updated Warehouse',
            'code' => 'UPDATED',
            'address' => 'Dhaka',
            'is_active' => '0',
        ])->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'code' => 'UPDATED', 'is_active' => false]);

        $this->actingAs($manager)->delete(route('warehouses.destroy', $warehouse))
            ->assertRedirect(route('warehouses.index'));

        $defaultWarehouse = Warehouse::where('is_default', true)->firstOrFail();
        $this->actingAs($manager)->delete(route('warehouses.destroy', $defaultWarehouse))
            ->assertSessionHasErrors('warehouse');
    }

    public function test_payment_account_edit_and_safe_delete_rules(): void
    {
        $manager = $this->manager(['manage_payment_accounts']);
        $account = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Temporary Account',
            'account_number' => '01700000000',
            'opening_balance' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($manager)->get(route('payment-accounts.edit', $account))->assertOk();
        $this->actingAs($manager)->put(route('payment-accounts.update', $account), [
            'payment_method' => 'bkash',
            'account_name' => 'Updated Account',
            'account_number' => '01700000000',
            'opening_balance' => 0,
            'status' => 'inactive',
        ])->assertRedirect(route('payment-accounts.index'));

        Expense::create([
            'expense_type' => 'other',
            'category' => 'office',
            'amount' => 100,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'expense_date' => now()->toDateString(),
        ]);

        $this->actingAs($manager)->delete(route('payment-accounts.destroy', $account))
            ->assertSessionHasErrors('payment_account');
        $this->assertDatabaseHas('payment_accounts', ['id' => $account->id]);

        $emptyAccount = PaymentAccount::create([
            'payment_method' => 'bank',
            'account_name' => 'Empty Account',
            'account_number' => 'EMPTY-001',
            'opening_balance' => 0,
            'status' => 'inactive',
        ]);
        $this->actingAs($manager)->delete(route('payment-accounts.destroy', $emptyAccount))
            ->assertRedirect(route('payment-accounts.index'));
    }

    public function test_organization_and_custom_olt_profile_delete_rules(): void
    {
        $manager = $this->manager(['manage_invoices', 'manage_mikrotik_routers']);
        $organization = Organization::create(['name' => 'Temporary Organization', 'is_default' => false, 'is_active' => false]);

        $this->actingAs($manager)->delete(route('organizations.destroy', $organization))
            ->assertRedirect(route('organizations.index'));

        $defaultOrganization = Organization::where('is_default', true)->firstOrFail();
        $this->actingAs($manager)->delete(route('organizations.destroy', $defaultOrganization))
            ->assertSessionHasErrors('organization');

        $profile = OltProtocolProfile::create([
            'key' => 'temporary_profile',
            'label' => 'Temporary Profile',
            'pon_interface_command' => 'interface epon {pon_port}',
        ]);
        $this->actingAs($manager)->delete(route('olt-onus.protocol-profiles.destroy', $profile))
            ->assertRedirect(route('olt-onus.protocol-profiles.index'));

        $builtIn = OltProtocolProfile::where('key', 'hsgq_epon')->firstOrFail();
        $this->actingAs($manager)->delete(route('olt-onus.protocol-profiles.destroy', $builtIn))
            ->assertSessionHasErrors('profile');
    }

    /** @param array<int, string> $permissionNames */
    private function manager(array $permissionNames): User
    {
        $manager = User::factory()->create();
        $manager->permissions()->attach(Permission::whereIn('name', $permissionNames)->pluck('id'));

        return $manager;
    }
}
