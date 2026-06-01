<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\MikrotikCustomerSyncService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
