<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_only_customer_can_be_created_without_connection_id(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Walk In Buyer',
            'phone' => '01711111111',
            'email' => '',
            'connection_id' => '',
            'address' => 'Kushtia',
            'status' => 'active',
        ])->assertRedirect(route('customers.index'));

        $customer = Customer::where('phone', '01711111111')->firstOrFail();

        $this->assertNull($customer->connection_id);
        $this->assertNull($customer->mikrotik_username);
        $this->assertNull($customer->mikrotik_password);
        $this->assertDatabaseMissing('subscriptions', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_connection_id_is_required_when_assigning_internet_package(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $package = InternetPackage::create([
            'name' => 'Home Basic',
            'speed' => '20 Mbps',
            'mikrotik_profile' => 'Home Basic',
            'monthly_price' => 1000,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'ISP Buyer',
            'phone' => '01722222222',
            'connection_id' => '',
            'address' => 'Kushtia',
            'status' => 'active',
            'internet_package_id' => $package->id,
            'start_date' => '2026-06-02',
        ])->assertSessionHasErrors('connection_id');
    }
}
