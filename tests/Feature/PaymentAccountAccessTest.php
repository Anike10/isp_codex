<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_access_page_is_super_admin_only(): void
    {
        $manager = User::factory()->create();
        $manager->permissions()->attach(Permission::where('name', 'manage_users')->firstOrFail());

        $this->actingAs($manager)->get(route('payment-account-access.index'))->assertForbidden();

        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)->get(route('payment-account-access.index'))->assertOk();
    }

    public function test_a_super_admin_can_grant_and_revoke_delegate_access(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = $this->userWith('manage_customers');
        $delegate = $this->userWith('manage_customers');

        $account = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Shared bKash',
            'account_number' => '01700000010',
            'opening_balance' => 0,
            'status' => 'active',
            'owner_user_id' => $owner->id,
        ]);

        // Before the grant the delegate cannot pay through it.
        $customer = $this->customer();
        $this->invoice($customer, 100);
        $this->actingAs($delegate)->post(route('customers.payments.store', $customer), [
            'amount' => 100, 'payment_method' => 'bkash',
            'payment_account_id' => $account->id, 'payment_date' => '2026-06-02',
        ])->assertSessionHasErrors('payment_account_id');

        // Grant.
        $this->actingAs($superAdmin)->put(route('payment-account-access.update', $account), [
            'user_ids' => [$delegate->id, $owner->id],
        ])->assertRedirect();
        $this->assertEqualsCanonicalizing([$delegate->id], $account->delegates()->pluck('users.id')->all());

        // Now the delegate can pay through it.
        $this->actingAs($delegate)->post(route('customers.payments.store', $customer), [
            'amount' => 100, 'payment_method' => 'bkash',
            'payment_account_id' => $account->id, 'payment_date' => '2026-06-02',
        ])->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseCount('payments', 1);

        // Revoke.
        $this->actingAs($superAdmin)->put(route('payment-account-access.update', $account), [
            'user_ids' => [],
        ])->assertRedirect();
        $this->assertCount(0, $account->delegates()->get());
    }

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', $permission)->firstOrFail());

        return $user->fresh();
    }

    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'Rahim Ahmed',
            'phone' => '01700000000',
            'connection_id' => 'KPS-1001',
            'address' => 'Kushtia',
            'status' => 'active',
        ]);
    }

    private function invoice(Customer $customer, float $amount): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, '2026-05'),
            'billing_month' => '2026-05',
            'invoice_type' => 'service',
            'subtotal' => $amount,
            'discount' => 0,
            'vat' => 0,
            'total' => $amount,
            'paid_amount' => 0,
            'due_amount' => $amount,
            'status' => 'unpaid',
            'due_date' => '2026-05-10',
        ]);
    }
}
