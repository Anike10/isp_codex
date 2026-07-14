<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_return_restores_stock_and_applies_credit_to_source_invoice(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Retail Customer',
            'phone' => '01711111111',
            'connection_id' => 'RC-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'account_balance' => 50,
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-RETURN-001',
            'brand' => 'BDCOM',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
        ]);

        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'ONU-RET-001',
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-07',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 3,
                    'unit_price' => 1200,
                    'serial_numbers' => 'ONU-RET-001',
                    'serialless_quantity' => 2,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $item = $invoice->items()->firstOrFail();

        $this->assertSame(0, $product->refresh()->stock_quantity);

        $this->actingAs($user)->post(route('sale-returns.store'), [
            'invoice_id' => $invoice->id,
            'return_no' => 'SR-TEST-001',
            'return_date' => '2026-07-14',
            'note' => 'Device returned by customer.',
            'items' => [
                [
                    'invoice_item_id' => $item->id,
                    'serial_numbers' => 'ONU-RET-001',
                    'serialless_quantity' => 1,
                ],
            ],
        ])->assertRedirect();

        $saleReturn = SaleReturn::where('return_no', 'SR-TEST-001')->firstOrFail();

        $this->assertSame(2, $product->refresh()->stock_quantity);
        $this->assertEquals(50, (float) $customer->refresh()->account_balance);
        $this->assertEquals(1200, (float) $invoice->refresh()->due_amount);
        $this->assertSame('partial', $invoice->status);
        $this->assertDatabaseHas('sale_return_items', [
            'sale_return_id' => $saleReturn->id,
            'invoice_item_id' => $item->id,
            'quantity' => 2,
            'serialless_quantity' => 1,
            'total' => 2400,
            'serial_numbers' => 'ONU-RET-001',
        ]);
        $this->assertDatabaseHas('product_serials', [
            'product_id' => $product->id,
            'serial_number' => 'ONU-RET-001',
            'status' => 'in_stock',
            'invoice_id' => null,
            'invoice_item_id' => null,
            'note' => 'Returned via SR-TEST-001',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 2,
            'serialless_quantity' => 1,
            'reference_no' => 'SR-TEST-001',
        ]);
        $this->assertDatabaseHas('sale_returns', [
            'id' => $saleReturn->id,
            'invoice_credit_amount' => 2400,
            'advance_credit_amount' => 0,
        ]);
        $this->assertDatabaseCount('customer_balance_transactions', 0);

        $this->actingAs($user)->post(route('sale-returns.store'), [
            'invoice_id' => $invoice->id,
            'return_no' => 'SR-TEST-001-FINAL',
            'return_date' => '2026-07-15',
            'items' => [[
                'invoice_item_id' => $item->id,
                'serialless_quantity' => 1,
            ]],
        ])->assertRedirect();

        $this->assertSame(3, $product->refresh()->stock_quantity);
        $this->assertEquals(0, (float) $invoice->refresh()->due_amount);
        $this->assertSame('returned', $invoice->status);
        $this->assertEquals(50, (float) $customer->refresh()->account_balance);

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Sale Returns')
            ->assertSee('SR-TEST-001')
            ->assertSee('returned');
    }

    public function test_sale_return_cannot_return_more_than_remaining_sold_quantity(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Retail Customer',
            'phone' => '01711111111',
            'connection_id' => 'RC-002',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'Router',
            'sku' => 'RTR-RETURN-001',
            'track_inventory' => true,
            'track_serial_numbers' => false,
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock_quantity' => 2,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-07',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => 1500,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $item = $invoice->items()->firstOrFail();

        $this->actingAs($user)->post(route('sale-returns.store'), [
            'invoice_id' => $invoice->id,
            'return_no' => 'SR-TEST-002',
            'return_date' => '2026-07-14',
            'items' => [
                [
                    'invoice_item_id' => $item->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(1, $product->refresh()->stock_quantity);
        $this->assertDatabaseCount('sale_returns', 0);
        $this->assertDatabaseCount('customer_balance_transactions', 0);
    }

    public function test_return_credit_exceeding_invoice_due_becomes_customer_advance(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Paid Return Customer', 'phone' => '01700000000', 'connection_id' => 'PAID-RET-1',
            'address' => '', 'status' => 'active', 'account_balance' => 25, 'is_customer' => true, 'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'Paid Router', 'sku' => 'PAID-ROUTER-1', 'track_inventory' => true,
            'track_serial_numbers' => false, 'purchase_price' => 1000, 'sale_price' => 1500,
            'stock_quantity' => 1, 'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'billing_month' => '2026-07',
            'items' => [['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1, 'unit_price' => 1500]],
            'discount_type' => 'amount', 'discount' => 0, 'vat_type' => 'amount', 'vat' => 0,
        ])->assertRedirect();
        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $invoice->update(['paid_amount' => 1500, 'due_amount' => 0, 'status' => 'paid']);
        $item = $invoice->items()->firstOrFail();

        $this->actingAs($user)->post(route('sale-returns.store'), [
            'invoice_id' => $invoice->id, 'return_no' => 'SR-PAID-001', 'return_date' => '2026-07-15',
            'items' => [['invoice_item_id' => $item->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertEquals(1525, (float) $customer->refresh()->account_balance);
        $this->assertDatabaseHas('sale_returns', ['return_no' => 'SR-PAID-001', 'invoice_credit_amount' => 0, 'advance_credit_amount' => 1500]);
        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id' => $customer->id, 'payment_method' => 'sale_return', 'direction' => 'credit', 'amount' => 1500,
        ]);
    }
}
