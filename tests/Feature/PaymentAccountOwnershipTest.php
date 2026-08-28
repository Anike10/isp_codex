<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAccountOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_only_record_money_through_accounts_they_own(): void
    {
        $owner = $this->userWith('manage_customers');
        $stranger = $this->userWith('manage_customers');

        $ownAccount = $this->account(['account_number' => '01700000001', 'owner_user_id' => $stranger->id]);
        $otherAccount = $this->account(['account_number' => '01700000002', 'owner_user_id' => $owner->id]);

        $customer = $this->customer();
        $invoice = $this->invoice($customer, 500);

        // Stranger owns $ownAccount, so paying through $otherAccount is refused.
        $this->actingAs($stranger)->post(route('customers.payments.store', $customer), [
            'amount' => 500,
            'payment_method' => 'bkash',
            'payment_account_id' => $otherAccount->id,
            'payment_date' => '2026-06-02',
        ])->assertSessionHasErrors('payment_account_id');
        $this->assertDatabaseCount('payments', 0);

        // Paying through the account they own succeeds.
        $this->actingAs($stranger)->post(route('customers.payments.store', $customer), [
            'amount' => 500,
            'payment_method' => 'bkash',
            'payment_account_id' => $ownAccount->id,
            'payment_date' => '2026-06-02',
        ])->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseHas('payments', ['payment_account_id' => $ownAccount->id, 'amount' => 500]);
        $this->assertSame(0.0, (float) $invoice->refresh()->due_amount);
    }

    public function test_a_super_admin_can_record_money_through_any_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $someoneElse = $this->userWith('manage_customers');

        $ownedByOther = $this->account(['account_number' => '01700000003', 'owner_user_id' => $someoneElse->id]);
        $unowned = $this->account(['account_number' => '01700000004', 'owner_user_id' => null]);

        $customer = $this->customer();
        $this->invoice($customer, 300);

        $this->actingAs($superAdmin)->post(route('customers.payments.store', $customer), [
            'amount' => 300,
            'payment_method' => 'bkash',
            'payment_account_id' => $ownedByOther->id,
            'payment_date' => '2026-06-02',
        ])->assertRedirect(route('customers.show', $customer));

        $customer2 = $this->customer('01900000000', 'KPS-2002');
        $this->invoice($customer2, 200);

        $this->actingAs($superAdmin)->post(route('customers.payments.store', $customer2), [
            'amount' => 200,
            'payment_method' => 'bkash',
            'payment_account_id' => $unowned->id,
            'payment_date' => '2026-06-02',
        ])->assertRedirect(route('customers.show', $customer2));

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_the_account_dropdown_lists_only_usable_accounts(): void
    {
        $owner = $this->userWith('manage_customers');
        $mine = $this->account(['account_name' => 'My Wallet', 'account_number' => '01700000005', 'owner_user_id' => $owner->id]);
        $theirs = $this->account(['account_name' => 'Their Wallet', 'account_number' => '01700000006', 'owner_user_id' => User::factory()->create()->id]);

        $customer = $this->customer();

        $this->actingAs($owner)->get(route('customers.payments.create', $customer))
            ->assertOk()
            ->assertSee('My Wallet')
            ->assertDontSee('Their Wallet');
    }

    public function test_only_a_super_admin_can_assign_the_account_owner(): void
    {
        $manager = $this->userWith('manage_payment_accounts');
        $target = User::factory()->create();

        $this->actingAs($manager)->post(route('payment-accounts.store'), [
            'payment_method' => 'bank',
            'account_name' => 'Manager Bank',
            'account_number' => 'BANK-1',
            'opening_balance' => 0,
            'status' => 'active',
            'owner_user_id' => $target->id,
        ])->assertRedirect(route('payment-accounts.index'));

        // A non-super manager cannot hand ownership to someone else.
        $this->assertDatabaseHas('payment_accounts', [
            'account_number' => 'BANK-1',
            'owner_user_id' => $manager->id,
        ]);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('payment-accounts.store'), [
            'payment_method' => 'bank',
            'account_name' => 'Assigned Bank',
            'account_number' => 'BANK-2',
            'opening_balance' => 0,
            'status' => 'active',
            'owner_user_id' => $target->id,
        ])->assertRedirect(route('payment-accounts.index'));

        $this->assertDatabaseHas('payment_accounts', [
            'account_number' => 'BANK-2',
            'owner_user_id' => $target->id,
        ]);
    }

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', $permission)->firstOrFail());

        return $user->fresh();
    }

    private function account(array $overrides = []): PaymentAccount
    {
        return PaymentAccount::create(array_merge([
            'payment_method' => 'bkash',
            'account_name' => 'Test Account',
            'account_number' => '01700000000',
            'opening_balance' => 0,
            'status' => 'active',
        ], $overrides));
    }

    private function customer(string $phone = '01700000000', string $connectionId = 'KPS-1001'): Customer
    {
        return Customer::create([
            'name' => 'Rahim Ahmed',
            'phone' => $phone,
            'connection_id' => $connectionId,
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
