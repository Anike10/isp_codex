<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_status_filter_can_show_unpaid_and_partial_invoices_together(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());

        $customer = Customer::create([
            'name' => 'Status Filter Customer',
            'phone' => '01710000010',
            'connection_id' => 'STATUS-FILTER-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);

        $this->createInvoice($customer, 'INV-FILTER-UNPAID', 'unpaid', 500, 0, 500);
        $this->createInvoice($customer, 'INV-FILTER-PARTIAL', 'partial', 500, 200, 300);
        $this->createInvoice($customer, 'INV-FILTER-PAID', 'paid', 500, 500, 0);

        $this->actingAs($user)
            ->get(route('invoices.index', ['status' => 'unpaid_partial']))
            ->assertOk()
            ->assertSee('Unpaid / Partial')
            ->assertSee('INV-FILTER-UNPAID')
            ->assertSee('INV-FILTER-PARTIAL')
            ->assertDontSee('INV-FILTER-PAID');
    }

    private function createInvoice(
        Customer $customer,
        string $invoiceNo,
        string $status,
        float $total,
        float $paidAmount,
        float $dueAmount,
    ): Invoice {
        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => $invoiceNo,
            'billing_month' => '2026-08',
            'invoice_type' => 'service',
            'subtotal' => $total,
            'discount' => 0,
            'vat' => 0,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'status' => $status,
        ]);
    }
}
