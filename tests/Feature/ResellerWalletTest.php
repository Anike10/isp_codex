<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class ResellerWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_wallet_payment_is_traced_and_deducted_atomically(): void
    {
        $reseller = $this->createReseller(10000, 1000);
        $customer = $this->createCustomer($reseller);
        $invoice = $this->createInvoice($customer, 800);
        $key = (string) Str::uuid();

        $allocation = $this->paymentService()->applyResellerWalletToInvoice($reseller, $invoice, [
            'amount' => 800,
            'operation_key' => $key,
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertSame('reseller_wallet', $allocation->source_type);
        $this->assertSame($reseller->id, $allocation->funded_by_customer_id);
        $this->assertSame(9200.0, (float) $reseller->refresh()->account_balance);
        $this->assertSame(0.0, (float) $invoice->refresh()->due_amount);
        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id' => $reseller->id,
            'invoice_id' => $invoice->id,
            'operation_key' => $key,
            'payment_method' => 'reseller_wallet',
            'direction' => 'debit',
            'amount' => 800,
            'balance_after' => 9200,
        ]);
    }

    public function test_daily_limit_is_enforced_across_multiple_payments(): void
    {
        $reseller = $this->createReseller(10000, 1000);
        $customer = $this->createCustomer($reseller);
        $first = $this->createInvoice($customer, 700, '2026-07');
        $second = $this->createInvoice($customer, 500, '2026-08');

        $this->paymentService()->applyResellerWalletToInvoice($reseller, $first, [
            'amount' => 700,
            'operation_key' => (string) Str::uuid(),
            'payment_date' => now()->toDateString(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Remaining today: 300.00');

        $this->paymentService()->applyResellerWalletToInvoice($reseller->refresh(), $second, [
            'amount' => 400,
            'operation_key' => (string) Str::uuid(),
            'payment_date' => now()->toDateString(),
        ]);
    }

    public function test_reseller_cannot_pay_an_unassigned_customer_invoice(): void
    {
        $reseller = $this->createReseller(1000, null);
        $invoice = $this->createInvoice($this->createCustomer(), 500);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not assigned');

        $this->paymentService()->applyResellerWalletToInvoice($reseller, $invoice, [
            'amount' => 100,
            'operation_key' => (string) Str::uuid(),
            'payment_date' => now()->toDateString(),
        ]);
    }

    public function test_repeated_operation_key_does_not_charge_wallet_twice(): void
    {
        $reseller = $this->createReseller(1000, null);
        $customer = $this->createCustomer($reseller);
        $invoice = $this->createInvoice($customer, 500);
        $key = (string) Str::uuid();
        $data = ['amount' => 200, 'operation_key' => $key, 'payment_date' => now()->toDateString()];

        $first = $this->paymentService()->applyResellerWalletToInvoice($reseller, $invoice, $data);
        $second = $this->paymentService()->applyResellerWalletToInvoice($reseller->refresh(), $invoice->refresh(), $data);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(800.0, (float) $reseller->refresh()->account_balance);
        $this->assertSame(300.0, (float) $invoice->refresh()->due_amount);
        $this->assertDatabaseCount('payment_allocations', 1);
        $this->assertDatabaseCount('customer_balance_transactions', 1);
    }

    public function test_reseller_portal_only_shows_assigned_customers(): void
    {
        $reseller = $this->createReseller(1000, 500);
        $assigned = $this->createCustomer($reseller, 'Assigned Customer');
        $other = $this->createCustomer(null, 'Other Customer');
        $this->createInvoice($assigned, 300, '2026-07');
        $this->createInvoice($other, 400, '2026-08');
        $user = User::factory()->create(['reseller_id' => $reseller->id]);
        $user->permissions()->attach(Permission::where('name', 'use_reseller_portal')->firstOrFail());

        $this->actingAs($user)
            ->get(route('reseller.dashboard'))
            ->assertOk()
            ->assertSee('Assigned Customer')
            ->assertDontSee('Other Customer');
    }

    public function test_admin_can_open_reseller_list_and_dashboard(): void
    {
        $reseller = $this->createReseller(1000, 500);
        $customer = $this->createCustomer($reseller, 'Assigned Customer');
        $this->createInvoice($customer, 300);
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_resellers')->firstOrFail());

        $this->actingAs($user)
            ->get(route('resellers.index'))
            ->assertOk()
            ->assertSee('Kushtia Reseller')
            ->assertSee('300.00');

        $this->actingAs($user)
            ->get(route('resellers.show', $reseller))
            ->assertOk()
            ->assertSee('Assigned Customer');
    }

    public function test_logged_in_reseller_generated_invoice_gets_commission_snapshot(): void
    {
        $reseller = $this->createReseller(1000, 500, 10);
        $customer = $this->createCustomer($reseller, 'Commission Customer');
        $this->assignPackage($customer, 500);
        $user = User::factory()->create(['reseller_id' => $reseller->id]);
        $user->permissions()->attach(Permission::where('name', 'use_reseller_portal')->firstOrFail());

        $this->actingAs($user)
            ->post(route('reseller.customers.invoices.store', $customer))
            ->assertRedirect(route('reseller.dashboard'));

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame($reseller->id, $invoice->reseller_id);
        $this->assertSame(500.0, (float) $invoice->gross_total);
        $this->assertSame(10.0, (float) $invoice->reseller_commission_percent);
        $this->assertSame(50.0, (float) $invoice->reseller_commission_amount);
        $this->assertSame(450.0, (float) $invoice->total);
        $this->assertSame(450.0, (float) $invoice->due_amount);
    }

    public function test_reseller_payment_entry_auto_creates_discounted_invoice(): void
    {
        $reseller = $this->createReseller(1000, 500, 10);
        $customer = $this->createCustomer($reseller, 'Payment Customer');
        $this->assignPackage($customer, 500);
        $user = User::factory()->create(['reseller_id' => $reseller->id]);
        $user->permissions()->attach(Permission::where('name', 'use_reseller_portal')->firstOrFail());

        $this->actingAs($user)
            ->get(route('reseller.customers.payments.create', $customer))
            ->assertOk()
            ->assertSee('Pay from Reseller Advance')
            ->assertSee('No cash/bank account entry will be created.')
            ->assertSee('Without Commission')
            ->assertDontSee('name="payment_account_id"', false)
            ->assertDontSee('name="payment_method"', false);

        $this->actingAs($user)->post(route('reseller.customers.payments.store', $customer), [
            'amount' => 450,
            'operation_key' => (string) Str::uuid(),
            'payment_date' => now()->toDateString(),
        ])->assertRedirect(route('reseller.dashboard'));

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame(500.0, (float) $invoice->gross_total);
        $this->assertSame(50.0, (float) $invoice->reseller_commission_amount);
        $this->assertSame(450.0, (float) $invoice->paid_amount);
        $this->assertSame(0.0, (float) $invoice->due_amount);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(550.0, (float) $reseller->refresh()->account_balance);
        $this->assertDatabaseMissing('payments', ['customer_id' => $customer->id]);
        $this->assertDatabaseHas('payment_allocations', [
            'customer_id' => $customer->id,
            'funded_by_customer_id' => $reseller->id,
            'invoice_id' => $invoice->id,
            'payment_id' => null,
            'source_type' => 'reseller_wallet',
            'amount' => 450,
        ]);
        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id' => $reseller->id,
            'invoice_id' => $invoice->id,
            'payment_account_id' => null,
            'payment_method' => 'reseller_wallet',
            'direction' => 'debit',
            'amount' => 450,
            'balance_after' => 550,
        ]);
    }

    public function test_reseller_can_pay_full_package_price_without_commission_from_wallet_only(): void
    {
        $reseller = $this->createReseller(1000, 1000, 10);
        $customer = $this->createCustomer($reseller, 'Full Price Customer');
        $this->assignPackage($customer, 500);
        $user = User::factory()->create(['reseller_id' => $reseller->id]);
        $user->permissions()->attach(Permission::where('name', 'use_reseller_portal')->firstOrFail());

        $this->actingAs($user)->post(route('reseller.customers.payments.store', $customer), [
            'amount' => 500,
            'operation_key' => (string) Str::uuid(),
            'payment_date' => now()->toDateString(),
            'without_commission' => 1,
        ])->assertRedirect(route('reseller.dashboard'));

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame($reseller->id, $invoice->reseller_id);
        $this->assertSame(0.0, (float) $invoice->reseller_commission_percent);
        $this->assertSame(0.0, (float) $invoice->reseller_commission_amount);
        $this->assertSame(500.0, (float) $invoice->gross_total);
        $this->assertSame(500.0, (float) $invoice->total);
        $this->assertSame(500.0, (float) $invoice->paid_amount);
        $this->assertSame(0.0, (float) $invoice->due_amount);
        $this->assertSame(500.0, (float) $reseller->refresh()->account_balance);
        $this->assertDatabaseMissing('payments', ['customer_id' => $customer->id]);
        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id' => $reseller->id,
            'invoice_id' => $invoice->id,
            'payment_account_id' => null,
            'payment_method' => 'reseller_wallet',
            'direction' => 'debit',
            'amount' => 500,
        ]);
    }

    public function test_commission_change_is_saved_with_effective_time(): void
    {
        $reseller = $this->createReseller(1000, 500, 10);
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $this->actingAs($user)->put(route('customers.update', $reseller), [
            'name' => $reseller->name,
            'phone' => $reseller->phone,
            'email' => '',
            'connection_id' => '',
            'address' => $reseller->address,
            'status' => 'active',
            'is_reseller' => 1,
            'reseller_daily_payment_limit' => 500,
            'reseller_commission_percent' => 15,
        ])->assertRedirect(route('customers.show', $reseller));

        $this->assertDatabaseHas('reseller_commission_histories', [
            'reseller_id' => $reseller->id,
            'old_percent' => 10,
            'new_percent' => 15,
            'changed_by' => $user->id,
        ]);
    }

    public function test_temporarily_disabling_reseller_role_does_not_erase_commission_terms(): void
    {
        $reseller = $this->createReseller(1000, 500, 5);
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $this->actingAs($user)->put(route('customers.update', $reseller), [
            'name' => $reseller->name,
            'phone' => $reseller->phone,
            'email' => '',
            'connection_id' => '',
            'address' => $reseller->address,
            'status' => 'active',
            'is_vendor' => 1,
        ])->assertRedirect(route('customers.show', $reseller));

        $reseller->refresh();
        $this->assertFalse($reseller->is_reseller);
        $this->assertSame(5.0, (float) $reseller->reseller_commission_percent);
        $this->assertSame(500.0, (float) $reseller->reseller_daily_payment_limit);
    }

    public function test_commission_invoice_print_can_toggle_net_and_full_payable(): void
    {
        $reseller = $this->createReseller(1000, 500, 10);
        $customer = $this->createCustomer($reseller, 'Print Customer');
        $invoice = $this->createInvoice($customer, 450);
        $invoice->update([
            'reseller_id' => $reseller->id,
            'reseller_commission_percent' => 10,
            'reseller_commission_amount' => 50,
            'gross_total' => 500,
            'subtotal' => 500,
            'discount' => 50,
            'total' => 450,
            'due_amount' => 450,
        ]);
        $user = User::factory()->create(['reseller_id' => $reseller->id]);
        $user->permissions()->attach(Permission::where('name', 'use_reseller_portal')->firstOrFail());

        $this->actingAs($user)
            ->get(route('reseller.invoices.print', ['invoice' => $invoice, 'organization_id' => Organization::defaultOrganization()->id]))
            ->assertOk()
            ->assertSee('Total Payable (show full amount)')
            ->assertSee('id="invoicePrintTotal">450.00', false)
            ->assertSee('Reseller commission (10.00%)')
            ->assertSee('500', false);
    }

    public function test_party_profile_shows_package_price_and_assigned_reseller(): void
    {
        $reseller = $this->createReseller(1000, 500, 12.5);
        $customer = $this->createCustomer($reseller, 'Profile Customer');
        $this->assignPackage($customer, 500);
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $this->actingAs($user)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('reseller-20m')
            ->assertSee('500.00')
            ->assertSee('Assigned reseller')
            ->assertSee('Kushtia Reseller')
            ->assertSee('Commission 12.50%');
    }

    public function test_reseller_party_profile_shows_contact_account_and_portal_details(): void
    {
        $reseller = $this->createReseller(10000, 1000, 10);
        $reseller->update(['email' => 'reseller@example.com']);
        $assigned = $this->createCustomer($reseller, 'Assigned Party');
        $loginUser = User::factory()->create([
            'name' => 'Reseller Login',
            'email' => 'login@example.com',
            'reseller_id' => $reseller->id,
        ]);
        $admin = User::factory()->create();
        $admin->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $this->actingAs($admin)
            ->get(route('customers.show', $reseller))
            ->assertOk()
            ->assertSee('Contact & Account', false)
            ->assertSee('reseller@example.com')
            ->assertSee('Reseller Details')
            ->assertSee('10,000.00')
            ->assertSee('1,000.00')
            ->assertSee('10.00%')
            ->assertSee($loginUser->email)
            ->assertSee($assigned->name);
    }

    private function paymentService(): PaymentService
    {
        return new PaymentService($this->createMock(MikrotikCustomerSyncService::class));
    }

    private function createReseller(float $balance, ?float $dailyLimit, float $commission = 0): Customer
    {
        return Customer::create([
            'name' => 'Kushtia Reseller',
            'phone' => '01700000001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => false,
            'is_reseller' => true,
            'account_balance' => $balance,
            'reseller_daily_payment_limit' => $dailyLimit,
            'reseller_commission_percent' => $commission,
        ]);
    }

    private function createCustomer(?Customer $reseller = null, string $name = 'Rahim Customer'): Customer
    {
        return Customer::create([
            'name' => $name,
            'phone' => '018000000'.random_int(10, 99),
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'reseller_id' => $reseller?->id,
        ]);
    }

    private function createInvoice(Customer $customer, float $amount, string $month = '2026-07'): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, $month),
            'billing_month' => $month,
            'invoice_type' => 'service',
            'subtotal' => $amount,
            'discount' => 0,
            'vat' => 0,
            'total' => $amount,
            'paid_amount' => 0,
            'due_amount' => $amount,
            'status' => 'unpaid',
            'due_date' => $month.'-10',
        ]);
    }

    private function assignPackage(Customer $customer, float $price): void
    {
        $package = InternetPackage::create([
            'name' => 'Reseller Package '.random_int(100, 999),
            'speed' => '20 Mbps',
            'mikrotik_profile' => 'reseller-20m',
            'monthly_price' => $price,
            'status' => 'active',
        ]);
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);
    }
}
