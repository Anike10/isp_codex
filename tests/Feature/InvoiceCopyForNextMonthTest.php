<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
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

    public function test_copy_for_next_month_does_not_duplicate_stock_product_or_serial_links(): void
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
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-COPY-001',
            'product_type' => 'serial_stock',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 10,
            'low_stock_alert' => 1,
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, '2026-04'),
            'billing_month' => '2026-04',
            'invoice_type' => 'product',
            'subtotal' => 1200,
            'discount' => 0,
            'vat' => 0,
            'total' => 1200,
            'paid_amount' => 0,
            'due_amount' => 1200,
            'status' => 'unpaid',
            'finalized_at' => now(),
        ]);
        $invoice->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_type' => 'serial_stock',
            'quantity' => 1,
            'unit_price' => 1200,
            'total' => 1200,
            'serial_numbers' => 'ONU-COPY-SN-001',
            'serialless_quantity' => 0,
        ]);

        $this->actingAs($user)->post(route('invoices.copy-next-month', $invoice))->assertRedirect();

        $copy = Invoice::where('billing_month', '2026-05')->firstOrFail();
        $copyItem = $copy->items()->firstOrFail();

        $this->assertNull($copyItem->product_id);
        $this->assertNull($copyItem->serial_numbers);
        $this->assertSame(0, $copyItem->serialless_quantity);
        $this->assertSame(10, $product->refresh()->stock_quantity);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'reference_no' => $copy->invoice_no,
        ]);
    }
}
