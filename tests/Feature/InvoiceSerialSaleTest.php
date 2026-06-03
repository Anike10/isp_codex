<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSerialSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_sale_can_use_available_product_serials(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Retail Customer',
            'phone' => '01711111111',
            'connection_id' => 'RC-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-SALE-001',
            'brand' => 'BDCOM',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 2,
            'low_stock_alert' => 1,
        ]);

        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'ONU-SN-001',
            'status' => 'in_stock',
        ]);
        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'ONU-SN-002',
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => 1200,
                    'serial_numbers' => 'ONU-SN-001',
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame(1, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'product_name' => 'ONU Device',
            'quantity' => 1,
            'serial_numbers' => 'ONU-SN-001',
        ]);
        $this->assertDatabaseHas('product_serials', [
            'product_id' => $product->id,
            'serial_number' => 'ONU-SN-001',
            'status' => 'sold',
            'note' => 'Sold via invoice '.$invoice->invoice_no,
        ]);
        $this->assertDatabaseHas('product_serials', [
            'product_id' => $product->id,
            'serial_number' => 'ONU-SN-002',
            'status' => 'in_stock',
        ]);
    }
}
