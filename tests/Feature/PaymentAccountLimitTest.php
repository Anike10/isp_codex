<?php

namespace Tests\Feature;

use App\Models\AccountDeposit;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAccountLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_capped_account_blocks_a_collection_that_would_pass_the_limit(): void
    {
        $owner = $this->userWith('manage_customers');
        $account = $this->account($owner, ['opening_balance' => 900, 'balance_limit' => 1000]);
        $customer = $this->customer();
        $this->invoice($customer, 200);

        $this->actingAs($owner)->post(route('customers.payments.store', $customer), [
            'amount' => 200,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-06-02',
        ])->assertSessionHasErrors('payment_account_id');
        $this->assertDatabaseCount('payments', 0);

        // Exactly reaching the limit is allowed.
        $this->invoice($this->customer('01900000000', 'KPS-3003'), 100);
        $customer2 = Customer::where('connection_id', 'KPS-3003')->firstOrFail();

        $this->actingAs($owner)->post(route('customers.payments.store', $customer2), [
            'amount' => 100,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-06-02',
        ])->assertRedirect(route('customers.show', $customer2));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_an_office_deposit_frees_capacity_again(): void
    {
        $owner = $this->userWith('manage_customers');
        $account = $this->account($owner, ['opening_balance' => 1000, 'balance_limit' => 1000]);
        $customer = $this->customer();
        $this->invoice($customer, 50);

        // Account is already at the limit.
        $this->actingAs($owner)->post(route('customers.payments.store', $customer), [
            'amount' => 50,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-06-02',
        ])->assertSessionHasErrors('payment_account_id');

        // Hand BDT 300 to the office, then the same collection goes through.
        $this->actingAs($owner)->post(route('account-deposits.store', $account), [
            'amount' => 300,
            'deposited_at' => '2026-06-02',
        ])->assertRedirect(route('payment-accounts.show', $account));
        $this->assertSame(700.0, $account->fresh()->liveBalance());

        $this->actingAs($owner)->post(route('customers.payments.store', $customer), [
            'amount' => 50,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-06-02',
        ])->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_a_deposit_cannot_be_more_than_the_account_balance(): void
    {
        $owner = $this->userWith('manage_customers');
        $account = $this->account($owner, ['opening_balance' => 300]);

        $this->actingAs($owner)->post(route('account-deposits.store', $account), [
            'amount' => 400,
            'deposited_at' => '2026-06-02',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('account_deposits', 0);
    }

    public function test_only_the_owner_or_a_super_admin_can_record_a_deposit(): void
    {
        $owner = $this->userWith('manage_payment_accounts');
        $stranger = $this->userWith('manage_payment_accounts');
        $account = $this->account($owner, ['opening_balance' => 500]);

        $this->actingAs($stranger)->get(route('account-deposits.create', $account))->assertForbidden();
        $this->actingAs($stranger)->post(route('account-deposits.store', $account), [
            'amount' => 100, 'deposited_at' => '2026-06-02',
        ])->assertForbidden();

        $account->delegates()->attach($stranger);
        $this->actingAs($stranger)->post(route('account-deposits.store', $account), [
            'amount' => 100, 'deposited_at' => '2026-06-02',
        ])->assertForbidden();

        $this->actingAs($owner)->post(route('account-deposits.store', $account), [
            'amount' => 100, 'deposited_at' => '2026-06-02',
        ])->assertRedirect(route('payment-accounts.show', $account));

        $superAdmin = User::factory()->superAdmin()->create();
        $superAdmin->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $this->actingAs($superAdmin)->post(route('account-deposits.store', $account), [
            'amount' => 50, 'deposited_at' => '2026-06-03',
        ])->assertRedirect(route('payment-accounts.show', $account));

        $this->assertDatabaseCount('account_deposits', 2);
    }

    public function test_an_uncapped_account_accepts_any_collection(): void
    {
        $owner = $this->userWith('manage_customers');
        $account = $this->account($owner, ['opening_balance' => 100000, 'balance_limit' => null]);
        $customer = $this->customer();
        $this->invoice($customer, 9000);

        $this->actingAs($owner)->post(route('customers.payments.store', $customer), [
            'amount' => 9000,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-06-02',
        ])->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseCount('payments', 1);
    }

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', $permission)->firstOrFail());

        return $user->fresh();
    }

    private function account(User $owner, array $overrides = []): PaymentAccount
    {
        return PaymentAccount::create(array_merge([
            'payment_method' => 'bkash',
            'account_name' => 'Capped Account',
            'account_number' => '01700000009',
            'opening_balance' => 0,
            'status' => 'active',
            'owner_user_id' => $owner->id,
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
