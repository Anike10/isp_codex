<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentDefaultsAndBkashDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_saved_payment_default_is_selected_only_for_that_login_user(): void
    {
        $user = $this->userWithPermission('manage_payments');
        $otherUser = $this->userWithPermission('manage_payments');
        $account = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Office bKash',
            'account_number' => '01800000000',
            'opening_balance' => 0,
            'status' => 'active',
        ]);
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, 1000);

        $this->actingAs($user)
            ->post(route('payments.store'), [
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'payment_method' => 'bkash',
                'payment_account_id' => $account->id,
                'payment_date' => '2026-08-11',
                'set_as_default' => '1',
            ])
            ->assertRedirect(route('payments.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'default_payment_method' => 'bkash',
            'default_payment_account_id' => $account->id,
        ]);

        $this->actingAs($user)
            ->get(route('payments.create'))
            ->assertOk()
            ->assertSee('Set as default')
            ->assertViewHas('paymentDefault', [
                'payment_method' => 'bkash',
                'payment_account_id' => $account->id,
            ]);

        $this->actingAs($otherUser)
            ->get(route('payments.create'))
            ->assertOk()
            ->assertViewHas('paymentDefault', [
                'payment_method' => 'cash',
                'payment_account_id' => null,
            ]);
    }

    public function test_bkash_webhook_creates_sender_device_account_and_posts_payment_to_it(): void
    {
        config(['services.bkash_sms.token' => null]);

        $customer = $this->createCustomer();
        $customer->update(['never_suspend' => true]);
        $invoice = $this->createInvoice($customer, 1000);

        $this->postJson(route('api.bkash-sms.store'), [
            'message' => 'You have received Tk 500.00 from 01700000000. TrxID ABC123XYZ Ref KPS-1001 at 12/08/2026 10:00 PM',
            'sender' => 'bKash',
            'sender_device' => 'Counter Redmi Phone',
        ])->assertOk()->assertJson([
            'status' => 'processed',
            'trx_id' => 'ABC123XYZ',
        ]);

        $account = PaymentAccount::query()
            ->where('payment_method', 'bkash')
            ->where('account_name', 'Counter Redmi Phone')
            ->firstOrFail();
        $payment = Payment::query()->where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertSame('sms-device:counter-redmi-phone', $account->account_number);
        $this->assertSame($account->id, $payment->payment_account_id);
        $this->assertSame(500.0, (float) $payment->amount);
        $this->assertSame('2026-08-12', $payment->payment_date?->format('Y-m-d'));
        $this->assertSame('2026-08-12', \Carbon\Carbon::parse(
            DB::table('bkash_sms_payments')->where('trx_id', 'ABC123XYZ')->value('payment_date')
        )->format('Y-m-d'));
    }

    public function test_bkash_advance_automatically_creates_the_next_invoice_and_renews_validity(): void
    {
        Carbon::setTestNow('2026-08-13 00:30:00');

        try {
            config(['services.bkash_sms.token' => null]);

            $customer = $this->createCustomer();
            $customer->update([
                'never_suspend' => true,
                'account_balance' => 2,
                'service_valid_from' => '2026-08-12',
                'service_valid_until' => '2026-09-11',
            ]);
            $package = InternetPackage::create([
                'name' => '30 Mb Star',
                'speed' => '30 Mbps',
                'monthly_price' => 10,
                'status' => 'active',
            ]);
            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-08-10',
                'status' => 'active',
            ]);
            $paidInvoice = $this->createInvoice($customer, 10);
            $paidInvoice->update([
                'paid_amount' => 10,
                'due_amount' => 0,
                'status' => 'paid',
            ]);

            $this->postJson(route('api.bkash-sms.store'), [
                'message' => 'You have received Tk 10.00 from 01700000000. TrxID AUTORENEW10 Ref KPS-1001 at 13/08/2026 12:15 AM',
                'sender' => 'bKash',
                'sender_device' => 'Renewal Phone',
            ])->assertOk()->assertJson([
                'status' => 'processed',
                'trx_id' => 'AUTORENEW10',
            ]);

            $renewalInvoice = Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('billing_month', '2026-09')
                ->firstOrFail();

            $this->assertSame('paid', $renewalInvoice->status);
            $this->assertSame(0.0, (float) $renewalInvoice->due_amount);
            $this->assertSame(2.0, (float) $customer->refresh()->account_balance);
            $this->assertSame('2026-10-11', $customer->service_valid_until?->format('Y-m-d'));
            $this->assertDatabaseHas('payment_allocations', [
                'customer_id' => $customer->id,
                'invoice_id' => $renewalInvoice->id,
                'source_type' => 'advance',
                'amount' => 10,
            ]);
            $this->assertDatabaseHas('bkash_sms_payments', [
                'trx_id' => 'AUTORENEW10',
                'status' => 'processed',
                'customer_id' => $customer->id,
                'invoice_id' => $renewalInvoice->id,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_customer_can_renew_one_month_from_advance_on_the_profile_page(): void
    {
        Carbon::setTestNow('2026-08-13 00:30:00');

        try {
            $user = $this->userWithPermission('manage_customers');
            $customer = $this->createCustomer();
            $customer->update([
                'account_balance' => 12,
                'service_valid_from' => '2026-08-12',
                'service_valid_until' => '2026-09-11',
            ]);
            $package = InternetPackage::create([
                'name' => '30 Mb Star',
                'speed' => '30 Mbps',
                'monthly_price' => 10,
                'status' => 'active',
            ]);
            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-08-10',
                'status' => 'active',
            ]);
            $paidInvoice = $this->createInvoice($customer, 10);
            $paidInvoice->update([
                'paid_amount' => 10,
                'due_amount' => 0,
                'status' => 'paid',
            ]);

            $this->actingAs($user)
                ->get(route('customers.show', $customer))
                ->assertOk()
                ->assertSee('Renew 1 month from advance', false)
                ->assertSee(route('customers.advance-renewal.store', $customer), false);

            $this->actingAs($user)
                ->post(route('customers.advance-renewal.store', $customer))
                ->assertRedirect(route('customers.show', $customer))
                ->assertSessionHas('success');

            $renewalInvoice = Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('billing_month', '2026-09')
                ->firstOrFail();

            $this->assertSame('paid', $renewalInvoice->status);
            $this->assertSame(2.0, (float) $customer->refresh()->account_balance);
            $this->assertSame('2026-10-11', $customer->service_valid_until?->format('Y-m-d'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_bkash_date_repair_updates_linked_ledgers_and_paid_validity(): void
    {
        $customer = $this->createCustomer();
        $oldDetail = '[2026-08-12 23:05] Paid validity: payment date 2026-12-08; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-12-08 to 2027-01-07. Payment note: Auto bKash SMS TrxID: DHC6EBL5IW';
        $customer->update([
            'account_balance' => 2,
            'service_valid_from' => '2026-12-08',
            'service_valid_until' => '2027-01-07',
            'service_validity_note' => $oldDetail,
            'notes' => $oldDetail,
        ]);
        $invoice = $this->createInvoice($customer, 10);
        $payment = Payment::create([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 12,
            'payment_method' => 'bkash',
            'payment_date' => '2026-12-08',
            'note' => 'Auto bKash SMS TrxID: DHC6EBL5IW',
        ]);

        DB::table('payment_allocations')->insert([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'source_type' => 'payment',
            'amount' => 10,
            'allocated_at' => '2026-12-08',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('customer_balance_transactions')->insert([
            'customer_id' => $customer->id,
            'payment_id' => $payment->id,
            'payment_method' => 'bkash',
            'direction' => 'credit',
            'amount' => 2,
            'balance_after' => 2,
            'transaction_date' => '2026-12-08',
            'reference' => 'DHC6EBL5IW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('bkash_sms_payments')->insert([
            'raw_sms' => 'You have received Tk 12.00 from 01700000000. TrxID DHC6EBL5IW at 12/08/2026 23:05',
            'trx_id' => 'DHC6EBL5IW',
            'ledger_trx_id' => 'DHC6EBL5IW',
            'amount' => 12,
            'payment_date' => '2026-12-08',
            'status' => 'processed',
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'created_at' => '2026-08-12 23:05:48',
            'updated_at' => '2026-08-12 23:05:48',
        ]);

        (require database_path('migrations/2026_08_12_000002_repair_bkash_day_first_payment_dates.php'))->up();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'payment_date' => '2026-08-12']);
        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'allocated_at' => '2026-08-12']);
        $this->assertDatabaseHas('customer_balance_transactions', ['payment_id' => $payment->id, 'transaction_date' => '2026-08-12']);
        $this->assertDatabaseHas('bkash_sms_payments', ['trx_id' => 'DHC6EBL5IW', 'payment_date' => '2026-08-12']);
        $this->assertSame('2026-08-12', $customer->refresh()->service_valid_from?->format('Y-m-d'));
        $this->assertSame('2026-09-11', $customer->service_valid_until?->format('Y-m-d'));
        $this->assertStringContainsString('payment date 12/08/2026', (string) $customer->service_validity_note);
        $this->assertStringContainsString('12/08/2026 to 11/09/2026', (string) $customer->service_validity_note);
    }

    private function userWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', $permission)->firstOrFail());

        return $user;
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Rahim Ahmed',
            'phone' => '01700000000',
            'connection_id' => 'KPS-1001',
            'address' => 'Kushtia',
            'status' => 'active',
        ]);
    }

    private function createInvoice(Customer $customer, float $amount): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, '2026-08'),
            'billing_month' => '2026-08',
            'invoice_type' => 'service',
            'subtotal' => $amount,
            'discount' => 0,
            'vat' => 0,
            'total' => $amount,
            'paid_amount' => 0,
            'due_amount' => $amount,
            'status' => 'unpaid',
            'due_date' => '2026-08-15',
        ]);
    }
}
