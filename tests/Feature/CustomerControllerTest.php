<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
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
            'is_customer' => '1',
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
            'is_customer' => '1',
            'internet_package_id' => $package->id,
            'start_date' => '2026-06-02',
        ])->assertSessionHasErrors('connection_id');
    }

    public function test_active_customer_without_paid_month_can_be_activated_until_next_month_date(): void
    {
        Carbon::setTestNow('2026-08-11 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $package = InternetPackage::create([
                'name' => 'Quick Activate',
                'speed' => '15 Mbps',
                'mikrotik_profile' => 'Quick Activate',
                'monthly_price' => 1000,
                'status' => 'active',
            ]);

            $customer = Customer::create([
                'name' => 'Quick Activation Customer',
                'phone' => '01788888888',
                'connection_id' => 'QACT-001',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
            ]);

            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-07-01',
                'status' => 'active',
            ]);

            $this->actingAs($user)
                ->from(route('customers.index'))
                ->post(route('customers.activate-next-date', $customer))
                ->assertRedirect(route('customers.index'))
                ->assertSessionHasNoErrors();

            $customer->refresh();
            $subscription->refresh();

            $this->assertSame('active', $customer->status);
            $this->assertSame('2026-09-11', $customer->service_valid_until?->format('Y-m-d'));
            $this->assertSame('2026-08-11', $customer->service_valid_from?->format('Y-m-d'));
            $this->assertSame('active', $subscription->status);
            $this->assertNull($subscription->end_date);
            $this->assertNull($customer->grace_used_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_inactive_customer_with_no_paid_month_shows_activate_action(): void
    {
        Carbon::setTestNow('2026-08-11 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $package = InternetPackage::create([
                'name' => 'Inactive Activate',
                'speed' => '10 Mbps',
                'mikrotik_profile' => 'Inactive Activate',
                'monthly_price' => 1000,
                'status' => 'active',
            ]);

            $customer = Customer::create([
                'name' => 'Inactive Quick Activation',
                'phone' => '01712121212',
                'connection_id' => 'IACT-001',
                'address' => 'Kushtia',
                'status' => 'inactive',
                'is_customer' => true,
            ]);
            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-07-01',
                'status' => 'inactive',
            ]);

            $this->actingAs($user)->get(route('customers.index'))
                ->assertOk()
                ->assertSee('Activate until 2026-09-11');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_quick_activation_without_package_returns_error(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'No package customer',
            'phone' => '01799999999',
            'connection_id' => 'NOPKG-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);

        $this->actingAs($user)
            ->post(route('customers.activate-next-date', $customer))
            ->assertSessionHasErrors('active_until');
    }

    public function test_expired_paid_period_shows_elapsed_days_and_null_due_date_is_disabled(): void
    {
        Carbon::setTestNow('2026-06-20 12:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $customer = Customer::create([
                'name' => 'Expired Customer',
                'phone' => '01733333333',
                'connection_id' => 'EXPIRED-001',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
            ]);
            Invoice::create([
                'customer_id' => $customer->id,
                'invoice_no' => 'INV-EXPIRED-001',
                'billing_month' => '2026-06',
                'invoice_type' => 'service',
                'subtotal' => 1000,
                'discount' => 0,
                'vat' => 0,
                'total' => 1000,
                'paid_amount' => 0,
                'due_amount' => 1000,
                'status' => 'unpaid',
                'due_date' => null,
            ]);

            $this->assertSame(-20, $customer->activeDaysRemaining());
            $this->actingAs($user)->get(route('customers.index'))
                ->assertOk()
                ->assertSee('Expired 20 days ago')
                ->assertSee('Assign package for grace');

            Artisan::call('billing:disable-overdue-customers', ['--date' => '2026-06-20']);

            $this->assertSame('inactive', $customer->refresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_expired_active_customer_with_package_can_receive_grace_period(): void
    {
        Carbon::setTestNow('2026-06-20 12:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $package = InternetPackage::create([
                'name' => 'Grace Package',
                'speed' => '20 Mbps',
                'mikrotik_profile' => 'Grace Package',
                'monthly_price' => 1000,
                'status' => 'active',
            ]);
            $customer = Customer::create([
                'name' => 'Grace Customer',
                'phone' => '01744444444',
                'connection_id' => 'GRACE-001',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
            ]);
            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-05-01',
                'status' => 'active',
            ]);
            Invoice::create([
                'customer_id' => $customer->id,
                'invoice_no' => 'INV-GRACE-001',
                'billing_month' => '2026-06',
                'invoice_type' => 'service',
                'subtotal' => 1000,
                'discount' => 0,
                'vat' => 0,
                'total' => 1000,
                'paid_amount' => 0,
                'due_amount' => 1000,
                'status' => 'unpaid',
            ]);

            $this->actingAs($user)
                ->from(route('customers.index'))
                ->post(route('customers.grace-period', $customer), ['grace_days' => 3])
                ->assertRedirect(route('customers.index'))
                ->assertSessionHasNoErrors();

            $customer->refresh();
            $this->assertSame('active', $customer->status);
            $this->assertSame(3, $customer->grace_days);
            $this->assertSame('2026-06-23', $customer->grace_until->format('Y-m-d'));
            $this->assertNotNull($customer->grace_used_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_manual_validity_change_requires_and_records_a_reason(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Validity Customer',
            'phone' => '01755555555',
            'connection_id' => 'VALID-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'service_valid_until' => '2026-06-30',
        ]);

        $this->actingAs($user)
            ->post(route('customers.service-validity.update', $customer), ['service_valid_until' => '2026-07-15'])
            ->assertSessionHasErrors('validity_note');

        $this->actingAs($user)
            ->post(route('customers.service-validity.update', $customer), [
                'service_valid_until' => '2026-07-15',
                'validity_note' => 'Customer paid late; manager approved extension.',
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertSame('2026-07-15', $customer->service_valid_until?->format('Y-m-d'));
        $this->assertStringContainsString('2026-06-30 → 2026-07-15', $customer->service_validity_note);
        $this->assertStringContainsString('manager approved extension', $customer->notes);
    }

    public function test_manual_past_validity_date_expires_subscription_for_mikrotik_sync(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $package = InternetPackage::create(['name' => 'Expired Plan', 'speed' => '10 Mbps', 'monthly_price' => 500, 'status' => 'active']);
        $customer = Customer::create(['name' => 'Expiry Customer', 'phone' => '01766666666', 'connection_id' => 'EXPIRE-001', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true]);
        $subscription = Subscription::create(['customer_id' => $customer->id, 'internet_package_id' => $package->id, 'start_date' => '2026-06-01', 'status' => 'active']);

        $this->actingAs($user)->post(route('customers.service-validity.update', $customer), [
            'service_valid_until' => now()->subDay()->toDateString(),
            'validity_note' => 'Payment period is over.',
        ])->assertRedirect();

        $this->assertSame('inactive', $customer->refresh()->status);
        $this->assertSame('inactive', $subscription->refresh()->status);
        $this->assertSame(now()->subDay()->toDateString(), $subscription->end_date?->format('Y-m-d'));
    }

    public function test_expired_party_payment_renews_the_remembered_package(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $package = InternetPackage::create(['name' => 'Remembered 30 MB', 'speed' => '30 Mbps', 'mikrotik_profile' => 'Remembered 30 MB', 'monthly_price' => 1000, 'status' => 'active']);
        $customer = Customer::create(['name' => 'Renew Customer', 'phone' => '01777777777', 'connection_id' => 'RENEW-001', 'address' => 'Kushtia', 'status' => 'inactive', 'is_customer' => true]);
        $subscription = Subscription::create(['customer_id' => $customer->id, 'internet_package_id' => $package->id, 'start_date' => '2026-06-01', 'end_date' => '2026-07-01', 'status' => 'inactive']);

        $this->actingAs($user)->post(route('customers.payments.store', $customer), [
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-18',
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertSame('active', $customer->refresh()->status);
        $this->assertSame('active', $subscription->refresh()->status);
        $this->assertNull($subscription->end_date);
        $this->assertDatabaseHas('invoices', ['customer_id' => $customer->id, 'billing_month' => '2026-07', 'due_amount' => 0]);
    }
}
