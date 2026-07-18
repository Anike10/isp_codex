<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_from_invoice_page_pays_that_invoice_before_older_customer_dues(): void
    {
        $customer = $this->createCustomer();

        $olderInvoice = $this->createInvoice($customer, '2026-04', 500, '2026-04-10');
        $selectedInvoice = $this->createInvoice($customer, '2026-05', 700, '2026-05-10');

        $this->paymentService()->recordPayment($selectedInvoice, [
            'amount' => 700,
            'payment_method' => 'cash',
            'payment_date' => '2026-05-15',
            'note' => 'Payment received for selected invoice.',
        ]);

        $this->assertSame(500.0, (float) $olderInvoice->refresh()->due_amount);
        $this->assertSame('unpaid', $olderInvoice->status);
        $this->assertSame(0.0, (float) $selectedInvoice->refresh()->due_amount);
        $this->assertSame(700.0, (float) $selectedInvoice->paid_amount);
        $this->assertSame('paid', $selectedInvoice->status);
    }

    public function test_selected_invoice_payment_pays_only_selected_due_invoices(): void
    {
        $customer = $this->createCustomer();

        $unselectedInvoice = $this->createInvoice($customer, '2026-04', 500, '2026-04-10');
        $firstSelectedInvoice = $this->createInvoice($customer, '2026-05', 700, '2026-05-10');
        $secondSelectedInvoice = $this->createInvoice($customer, '2026-06', 300, '2026-06-10');

        $payment = $this->paymentService()->recordPaymentForInvoices($customer, [
            $firstSelectedInvoice->id,
            $secondSelectedInvoice->id,
        ], [
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-15',
            'note' => 'Bulk payment from selected invoices.',
        ]);

        $this->assertSame(1000.0, (float) $payment->amount);
        $this->assertSame(500.0, (float) $unselectedInvoice->refresh()->due_amount);
        $this->assertSame('unpaid', $unselectedInvoice->status);
        $this->assertSame(0.0, (float) $firstSelectedInvoice->refresh()->due_amount);
        $this->assertSame('paid', $firstSelectedInvoice->status);
        $this->assertSame(0.0, (float) $secondSelectedInvoice->refresh()->due_amount);
        $this->assertSame('paid', $secondSelectedInvoice->status);
        $this->assertSame(2, $payment->allocations()->count());
    }

    public function test_payment_voucher_shows_all_allocated_invoices(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payments')->firstOrFail());
        $customer = $this->createCustomer();
        $firstInvoice = $this->createInvoice($customer, '2026-05', 2500, '2026-05-10');
        $secondInvoice = $this->createInvoice($customer, '2026-06', 750, '2026-06-10');

        $payment = $this->paymentService()->recordPaymentForInvoices($customer, [
            $firstInvoice->id,
            $secondInvoice->id,
        ], [
            'amount' => 3250,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-12',
            'note' => 'Bulk payment from selected invoices.',
        ]);

        $this->actingAs($user)
            ->get(route('payments.voucher', $payment))
            ->assertOk()
            ->assertSee('BDT 3,250.00')
            ->assertSee('2 invoices')
            ->assertSee($firstInvoice->invoice_no)
            ->assertSee('BDT 2,500.00')
            ->assertSee($secondInvoice->invoice_no)
            ->assertSee('BDT 750.00');
    }

    public function test_payment_detail_page_shows_allocations_and_payment_index_links_to_details(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payments')->firstOrFail());
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $user->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $customer = $this->createCustomer();
        $firstInvoice = $this->createInvoice($customer, '2026-05', 2500, '2026-05-10');
        $secondInvoice = $this->createInvoice($customer, '2026-06', 750, '2026-06-10');

        $payment = $this->paymentService()->recordPaymentForInvoices($customer, [
            $firstInvoice->id,
            $secondInvoice->id,
        ], [
            'amount' => 3250,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-12',
            'note' => 'Bulk payment from selected invoices.',
        ]);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Payment #'.$payment->id)
            ->assertSee('3,250.00')
            ->assertSee($firstInvoice->invoice_no)
            ->assertSee('2,500.00')
            ->assertSee($secondInvoice->invoice_no)
            ->assertSee('750.00');

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('data-href="'.route('payments.show', $payment).'"', false)
            ->assertSee(route('payments.show', $payment), false)
            ->assertSee(route('accounting.ledger', ['customer_id' => $customer->id]), false)
            ->assertSee(route('invoices.show', $firstInvoice), false)
            ->assertSee(route('payment-accounts.cash-ledger'), false);

        $this->actingAs($user)
            ->get(route('invoices.show', $secondInvoice))
            ->assertOk()
            ->assertSee(route('payments.show', $payment), false)
            ->assertDontSee(route('payments.voucher', $payment), false);
    }

    public function test_payment_pages_show_entry_operator_and_exact_entry_time(): void
    {
        $this->travelTo(Carbon::parse('2026-07-18 10:45:30'));
        $user = User::factory()->create(['name' => 'Payment Operator']);
        $user->permissions()->attach(Permission::where('name', 'manage_payments')->firstOrFail());
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, '2026-07', 500, '2026-07-18');

        $this->actingAs($user);
        $payment = $this->paymentService()->recordPayment($invoice, [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-18',
        ]);

        $this->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Payment Operator')
            ->assertSee('2026-07-18 10:45:30 AM');

        $this->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Entered By:')
            ->assertSee('Payment Operator')
            ->assertSee('Entered At:')
            ->assertSee('2026-07-18 10:45:30 AM');
    }

    public function test_direct_advance_collections_are_visible_on_the_payment_index(): void
    {
        $this->travelTo(Carbon::parse('2026-07-18 11:15:00'));
        $user = User::factory()->create(['name' => 'Advance Operator']);
        $user->permissions()->attach(Permission::where('name', 'manage_payments')->firstOrFail());
        $customer = $this->createCustomer();

        $this->actingAs($user);
        $this->paymentService()->addAdvanceCredit($customer, [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-18',
            'note' => 'Advance collected at office.',
        ]);

        $this->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Advance Collections')
            ->assertSee('Rahim Ahmed')
            ->assertSee('500.00')
            ->assertSee('Advance Operator')
            ->assertSee('2026-07-18 11:15:00 AM')
            ->assertSee('Advance collected at office.');
    }

    public function test_payment_detail_explains_fractional_advance_remainder(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payments')->firstOrFail());
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, '2026-07', 0.04, '2026-07-18');

        $payment = $this->paymentService()->recordPayment($invoice, [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-18',
        ]);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Applied to Invoice(s):')
            ->assertSee('0.04')
            ->assertSee('Added to Advance:')
            ->assertSee('499.96')
            ->assertSee('The payment exceeded the invoice amount by 499.96');
    }

    public function test_payment_index_hides_ledger_links_without_account_permissions(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payments')->firstOrFail());
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, '2026-05', 500, '2026-05-10');

        $payment = $this->paymentService()->recordPaymentForInvoices($customer, [$invoice->id], [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-12',
        ]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee(route('payments.show', $payment), false)
            ->assertDontSee(route('invoices.show', $invoice), false)
            ->assertDontSee(route('accounting.ledger', ['customer_id' => $customer->id]), false)
            ->assertDontSee(route('payment-accounts.cash-ledger'), false);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee($invoice->invoice_no)
            ->assertDontSee(route('invoices.show', $invoice), false);
    }

    public function test_payment_account_ledger_can_be_filtered_by_date_search_and_amount(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $customer = $this->createCustomer();
        $account = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Office bKash',
            'account_number' => '01800000000',
            'opening_balance' => 100,
            'status' => 'active',
        ]);
        $firstInvoice = $this->createInvoice($customer, '2026-05', 2500, '2026-05-10');
        $secondInvoice = $this->createInvoice($customer, '2026-06', 750, '2026-06-10');

        $this->paymentService()->recordPaymentForInvoices($customer, [$firstInvoice->id], [
            'amount' => 2500,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-06-20',
            'note' => 'May bill collection.',
        ]);
        $this->paymentService()->recordPaymentForInvoices($customer, [$secondInvoice->id], [
            'amount' => 750,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-07-12',
            'note' => 'June bill collection.',
        ]);

        $this->actingAs($user)
            ->get(route('payment-accounts.show', [
                'payment_account' => $account,
                'from' => '2026-07-01',
                'to' => '2026-07-31',
                'search' => '2026-06',
                'min_amount' => 700,
                'max_amount' => 800,
            ]))
            ->assertOk()
            ->assertSee('Filtered Collection')
            ->assertSee('750.00')
            ->assertSee($secondInvoice->invoice_no)
            ->assertDontSee($firstInvoice->invoice_no);
    }

    public function test_payment_account_ledger_running_balance_includes_previous_paginated_rows(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $customer = $this->createCustomer();
        $account = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Office bKash',
            'account_number' => '01800000000',
            'opening_balance' => 100,
            'status' => 'active',
        ]);

        for ($i = 0; $i < 26; $i++) {
            $date = Carbon::create(2026, 1, 1)->addMonths($i);
            $invoice = $this->createInvoice($customer, $date->format('Y-m'), 10, $date->copy()->day(10)->toDateString());

            $this->paymentService()->recordPaymentForInvoices($customer, [$invoice->id], [
                'amount' => 10,
                'payment_method' => 'bkash',
                'payment_account_id' => $account->id,
                'payment_date' => $date->copy()->day(20)->toDateString(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('payment-accounts.show', [
                'payment_account' => $account,
                'per_page' => 25,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('Before Page')
            ->assertSee('350.00')
            ->assertSee('360.00');
    }

    public function test_payment_account_ledger_running_balance_includes_prior_mixed_rows(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $customer = $this->createCustomer();
        $account = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Office bKash',
            'account_number' => '01800000000',
            'opening_balance' => 100,
            'status' => 'active',
        ]);

        for ($i = 0; $i < 13; $i++) {
            $date = Carbon::create(2026, 1, 1)->addMonths($i);
            $invoice = $this->createInvoice($customer, $date->format('Y-m'), 10, $date->copy()->day(10)->toDateString());

            $this->paymentService()->recordPaymentForInvoices($customer, [$invoice->id], [
                'amount' => 10,
                'payment_method' => 'bkash',
                'payment_account_id' => $account->id,
                'payment_date' => $date->copy()->day(20)->toDateString(),
            ]);
            Expense::create([
                'expense_type' => 'other',
                'category' => 'transport',
                'amount' => 4,
                'payment_method' => 'bkash',
                'payment_account_id' => $account->id,
                'expense_date' => $date->copy()->day(20)->toDateString(),
                'note' => 'Mixed ledger expense '.($i + 1),
            ]);
        }

        $this->actingAs($user)
            ->get(route('payment-accounts.show', [
                'payment_account' => $account,
                'per_page' => 25,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('Before Page')
            ->assertSee('182.00')
            ->assertSee('178.00')
            ->assertSee('Mixed ledger expense 13');
    }

    public function test_payment_account_ledger_includes_expense_debits_in_running_balance(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $customer = $this->createCustomer();
        $account = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Office bKash',
            'account_number' => '01800000000',
            'opening_balance' => 100,
            'status' => 'active',
        ]);
        $invoice = $this->createInvoice($customer, '2026-06', 750, '2026-06-10');

        $this->paymentService()->recordPaymentForInvoices($customer, [$invoice->id], [
            'amount' => 750,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-07-12',
            'note' => 'June bill collection.',
        ]);
        Expense::create([
            'expense_type' => 'other',
            'category' => 'rent',
            'amount' => 250,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'expense_date' => '2026-07-13',
            'note' => 'Office rent paid.',
        ]);

        $this->actingAs($user)
            ->get(route('payment-accounts.show', $account))
            ->assertOk()
            ->assertSee('Debit')
            ->assertSee('Office rent paid.')
            ->assertSee('250.00')
            ->assertSee('600.00');
    }

    public function test_cash_ledger_includes_cash_expense_debits_in_running_balance(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, '2026-06', 500, '2026-06-10');

        $this->paymentService()->recordPaymentForInvoices($customer, [$invoice->id], [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-12',
            'note' => 'Cash bill collection.',
        ]);
        Expense::create([
            'expense_type' => 'other',
            'category' => 'transport',
            'amount' => 125,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-13',
            'note' => 'Cash transport paid.',
        ]);

        $this->actingAs($user)
            ->get(route('payment-accounts.cash-ledger'))
            ->assertOk()
            ->assertSee('Debit')
            ->assertSee('Cash transport paid.')
            ->assertSee('125.00')
            ->assertSee('375.00');
    }

    public function test_payment_account_ledger_and_index_include_direct_advance_receipts(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $customer = $this->createCustomer();
        $account = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Advance bKash',
            'account_number' => '01800000001',
            'opening_balance' => 100,
            'status' => 'active',
        ]);

        $this->paymentService()->addAdvanceCredit($customer, [
            'amount' => 300,
            'payment_method' => 'bkash',
            'payment_account_id' => $account->id,
            'payment_date' => '2026-06-01',
            'reference' => 'ADV-LEDGER-001',
            'note' => 'Direct advance collection.',
        ]);

        $this->actingAs($user)
            ->get(route('payment-accounts.show', $account))
            ->assertOk()
            ->assertSee('Advance')
            ->assertSee('Direct advance collection.')
            ->assertSee('300.00')
            ->assertSee('400.00');

        $this->actingAs($user)
            ->get(route('payment-accounts.index'))
            ->assertOk()
            ->assertSee('Advance bKash')
            ->assertSee('300.00')
            ->assertSee('400.00');

        $this->actingAs($user)
            ->get(route('payment-accounts.show', [
                'payment_account' => $account,
                'search' => 'ADV-LEDGER-001',
            ]))
            ->assertOk()
            ->assertSee('Direct advance collection.');

        $this->actingAs($user)
            ->get(route('payment-accounts.show', [
                'payment_account' => $account,
                'from' => '2026-07-01',
            ]))
            ->assertOk()
            ->assertSee('Before Filter')
            ->assertSee('400.00');
    }

    public function test_cash_ledger_includes_direct_advance_but_payment_remainder_is_not_double_counted(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_payment_accounts')->firstOrFail());
        $customer = $this->createCustomer();

        $this->paymentService()->addAdvanceCredit($customer, [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-05-01',
            'reference' => 'CASH-ADV-001',
            'note' => 'Cash advance collection.',
        ]);

        $invoice = $this->createInvoice($customer, '2026-06', 500, '2026-06-10');
        $this->paymentService()->recordPayment($invoice, [
            'amount' => 700,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-15',
            'note' => 'Cash payment with remainder.',
        ]);

        $this->actingAs($user)
            ->get(route('payment-accounts.cash-ledger'))
            ->assertOk()
            ->assertSee('Cash advance collection.')
            ->assertSee('1,200.00')
            ->assertDontSee('1,400.00');
    }

    public function test_paid_invoice_cannot_accept_another_payment(): void
    {
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, '2026-05', 700, '2026-05-10');

        $this->paymentService()->recordPayment($invoice, [
            'amount' => 700,
            'payment_method' => 'cash',
            'payment_date' => '2026-05-15',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This invoice is already paid.');

        $this->paymentService()->recordPayment($invoice->refresh(), [
            'amount' => 700,
            'payment_method' => 'cash',
            'payment_date' => '2026-05-16',
        ]);
    }

    public function test_advance_credit_can_be_added_without_an_invoice(): void
    {
        $customer = $this->createCustomer();

        $transaction = $this->paymentService()->addAdvanceCredit($customer, [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-05-15',
            'reference' => 'ADV-001',
            'note' => 'Customer paid before invoice.',
        ]);

        $this->assertSame(500.0, (float) $customer->refresh()->account_balance);
        $this->assertSame('credit', $transaction->direction);
        $this->assertSame(500.0, (float) $transaction->balance_after);
    }

    public function test_advance_balance_can_be_applied_to_a_selected_due_invoice(): void
    {
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, '2026-05', 700, '2026-05-10');

        $this->paymentService()->addAdvanceCredit($customer, [
            'amount' => 700,
            'payment_method' => 'cash',
            'payment_date' => '2026-05-01',
        ]);

        $allocation = $this->paymentService()->applyAdvanceToInvoice($customer->refresh(), $invoice, [
            'amount' => 700,
            'payment_date' => '2026-05-15',
            'note' => 'Apply advance to selected invoice.',
        ]);

        $this->assertSame(0.0, (float) $customer->refresh()->account_balance);
        $this->assertSame(0.0, (float) $invoice->refresh()->due_amount);
        $this->assertSame(700.0, (float) $invoice->paid_amount);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('advance', $allocation->source_type);
        $this->assertSame(700.0, (float) $allocation->amount);
    }

    public function test_advance_payment_entry_can_allocate_part_to_due_invoice_and_keep_remainder(): void
    {
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, '2026-05', 700, '2026-05-10');

        $this->paymentService()->addAdvanceCreditAndApplyToInvoices($customer, [
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_date' => '2026-05-15',
            'note' => 'Advance entry with invoice allocation.',
        ], [
            $invoice->id => 700,
        ]);

        $this->assertSame(300.0, (float) $customer->refresh()->account_balance);
        $this->assertSame(0.0, (float) $invoice->refresh()->due_amount);
        $this->assertSame(700.0, (float) $invoice->paid_amount);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_customer_payment_route_auto_pays_oldest_due_invoice_first(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = $this->createCustomer();
        $package = InternetPackage::create([
            'name' => 'Home Basic',
            'speed' => '20 Mbps',
            'mikrotik_profile' => 'Home Basic',
            'monthly_price' => 1000,
            'description' => 'Basic package',
            'status' => 'active',
        ]);

        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => '2026-04-01',
            'status' => 'active',
        ]);

        $olderInvoice = $this->createInvoice($customer, '2026-04', 500, '2026-04-10');

        $this->actingAs($user)->post(route('customers.payments.store', $customer), [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-02',
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertSame(0.0, (float) $olderInvoice->refresh()->due_amount);
        $this->assertSame('paid', $olderInvoice->status);

        $currentInvoice = Invoice::where('customer_id', $customer->id)
            ->where('billing_month', now()->format('Y-m'))
            ->where('invoice_type', 'service')
            ->firstOrFail();

        $this->assertSame(1000.0, (float) $currentInvoice->due_amount);
        $this->assertSame('unpaid', $currentInvoice->status);
    }

    public function test_customer_payment_rejects_account_from_another_payment_method(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice($customer, '2026-05', 500, '2026-05-10');
        $bkashAccount = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Office bKash',
            'account_number' => '01800000000',
            'opening_balance' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('customers.payments.store', $customer), [
            'amount' => 500,
            'payment_method' => 'bank',
            'payment_account_id' => $bkashAccount->id,
            'payment_date' => '2026-06-02',
        ])->assertSessionHasErrors('payment_account_id');

        $this->assertSame(500.0, (float) $invoice->refresh()->due_amount);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cash_advance_route_discards_a_forged_payment_account_id(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = $this->createCustomer();
        $bkashAccount = PaymentAccount::create([
            'payment_method' => 'bkash',
            'account_name' => 'Office bKash',
            'account_number' => '01800000000',
            'opening_balance' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('customers.advance-payments.store', $customer), [
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_account_id' => $bkashAccount->id,
            'payment_date' => '2026-06-02',
            'reference' => 'CASH-NO-ACCOUNT',
        ])->assertRedirect(route('customers.payments.create', $customer));

        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id' => $customer->id,
            'payment_method' => 'cash',
            'payment_account_id' => null,
            'amount' => 500,
        ]);
    }

    public function test_paid_validity_deducts_consumed_grace_days_from_the_new_month(): void
    {
        $customer = $this->createCustomer();
        $customer->update([
            'grace_days' => 5,
            'grace_until' => '2026-04-09',
            'grace_used_at' => '2026-04-04 10:00:00',
        ]);
        $invoice = $this->createInvoice($customer, '2026-04', 1000, '2026-04-10');

        $this->paymentService()->recordPayment($invoice, [
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_date' => '2026-04-10',
            'note' => 'Paid after grace.',
        ]);

        $customer->refresh();

        // 10 April to 9 May is 30 days; after 5 grace days, paid validity is 25 days.
        $this->assertSame('2026-04-10', $customer->service_valid_from?->format('Y-m-d'));
        $this->assertSame('2026-05-04', $customer->service_valid_until?->format('Y-m-d'));
        $this->assertNull($customer->grace_days);
        $this->assertNull($customer->grace_until);
        $this->assertNull($customer->grace_used_at);
        $this->assertStringContainsString('grace deducted 5 day(s)', $customer->service_validity_note);
        $this->assertStringContainsString('Paid after grace.', $customer->notes);
    }

    private function paymentService(): PaymentService
    {
        return new PaymentService($this->createMock(MikrotikCustomerSyncService::class));
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

    private function createInvoice(Customer $customer, string $billingMonth, float $amount, string $dueDate): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, $billingMonth),
            'billing_month' => $billingMonth,
            'invoice_type' => 'service',
            'subtotal' => $amount,
            'discount' => 0,
            'vat' => 0,
            'total' => $amount,
            'paid_amount' => 0,
            'due_amount' => $amount,
            'status' => 'unpaid',
            'due_date' => $dueDate,
        ]);
    }
}
