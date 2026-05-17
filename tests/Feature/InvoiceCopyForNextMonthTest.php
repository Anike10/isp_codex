<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCopyForNextMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_copied_for_next_month_with_same_items_and_prices(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());

        $customer = Customer::create([
            'name' => 'Rahim Ahmed',
            'phone' => '01700000000',
            'connection_id' => 'KPS-1001',
            'address' => 'Kushtia',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, '2026-04'),
            'billing_month' => '2026-04',
            'invoice_type' => 'product',
            'subtotal' => 1500,
            'discount' => 100,
            'vat' => 70,
            'total' => 1470,
            'paid_amount' => 500,
            'due_amount' => 970,
            'status' => 'partial',
            'finalized_at' => now(),
            'due_date' => '2026-04-10',
        ]);

        $invoice->items()->create([
            'product_name' => 'Router',
            'quantity' => 1,
            'unit_price' => 1200,
            'total' => 1200,
        ]);

        $invoice->items()->create([
            'product_name' => 'Cable',
            'quantity' => 15,
            'unit_price' => 20,
            'total' => 300,
        ]);

        Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, '2026-05'),
            'billing_month' => '2026-05',
            'invoice_type' => 'service',
            'subtotal' => 500,
            'discount' => 0,
            'vat' => 0,
            'total' => 500,
            'paid_amount' => 0,
            'due_amount' => 500,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->post(route('invoices.copy-next-month', $invoice));

        $copy = Invoice::where('billing_month', '2026-05')
            ->where('invoice_type', 'product')
            ->firstOrFail();
        $response->assertRedirect(route('invoices.show', $copy));

        $this->assertSame($customer->id, $copy->customer_id);
        $this->assertSame('INV-2026-05-00001-02', $copy->invoice_no);
        $this->assertSame('product', $copy->invoice_type);
        $this->assertSame('May 2026', $copy->formatted_billing_month);
        $this->assertSame(0.0, (float) $copy->paid_amount);
        $this->assertSame(1470.0, (float) $copy->due_amount);
        $this->assertSame('unpaid', $copy->status);
        $this->assertNull($copy->finalized_at);
        $this->assertSame('2026-05-10', $copy->due_date->format('Y-m-d'));

        $this->assertCount(2, $copy->items);
        $this->assertSame('Router', $copy->items[0]->product_name);
        $this->assertSame(1, $copy->items[0]->quantity);
        $this->assertSame(1200.0, (float) $copy->items[0]->unit_price);
        $this->assertSame('Cable', $copy->items[1]->product_name);
        $this->assertSame(15, $copy->items[1]->quantity);
        $this->assertSame(20.0, (float) $copy->items[1]->unit_price);
    }
}
