<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\AppSetting;
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

    public function test_invoice_sale_updates_quantity_from_serial_range_count(): void
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
            'name' => 'HP 3200',
            'sku' => 'HP-3200',
            'brand' => 'HP',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
        ]);

        foreach (['1002', '1003', '1004'] as $serialNumber) {
            ProductSerial::create([
                'product_id' => $product->id,
                'serial_number' => $serialNumber,
                'status' => 'in_stock',
            ]);
        }

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => 1200,
                    'serial_numbers' => '1002-1004',
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame(0, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'total' => 3600,
            'serial_numbers' => '1002-1004',
        ]);

        foreach (['1002', '1003', '1004'] as $serialNumber) {
            $this->assertDatabaseHas('product_serials', [
                'product_id' => $product->id,
                'serial_number' => $serialNumber,
                'status' => 'sold',
            ]);
        }
    }

    public function test_invoice_update_recalculates_percentage_vat_after_item_removal(): void
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

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_name' => 'Router',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
                [
                    'product_name' => 'Cable',
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'percent',
            'vat' => 10,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame('percent', $invoice->vat_type);
        $this->assertEquals(10, $invoice->vat_value);
        $this->assertEquals(150, $invoice->vat);
        $this->assertEquals(1650, $invoice->total);

        $this->actingAs($user)->put(route('invoices.update', $invoice), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_name' => 'Router',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'percent',
            'vat' => 10,
        ])->assertRedirect();

        $invoice->refresh();

        $this->assertSame('percent', $invoice->vat_type);
        $this->assertEquals(10, $invoice->vat_value);
        $this->assertEquals(100, $invoice->vat);
        $this->assertEquals(1100, $invoice->total);
        $this->assertCount(1, $invoice->items()->get());
    }

    public function test_invoice_notes_are_saved_and_customer_documents_only_show_public_note_when_enabled(): void
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

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_name' => 'Router',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
            'public_note' => 'Customer-visible setup note.',
            'private_note' => 'Office-only margin note.',
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame('Customer-visible setup note.', $invoice->public_note);
        $this->assertFalse($invoice->show_public_note);
        $this->assertSame('Office-only margin note.', $invoice->private_note);

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Customer-visible setup note.')
            ->assertSee('Office-only margin note.');

        $this->actingAs($user)->get(route('invoices.challan', $invoice))
            ->assertOk()
            ->assertDontSee('Customer-visible setup note.')
            ->assertDontSee('Office-only margin note.');

        $this->actingAs($user)->put(route('invoices.update', $invoice), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_name' => 'Router',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
            'public_note' => 'Customer-visible setup note.',
            'show_public_note' => '1',
            'private_note' => 'Office-only margin note.',
        ])->assertRedirect();

        $invoice->refresh();

        $this->assertTrue($invoice->show_public_note);

        foreach (['invoices.challan', 'invoices.quotation', 'invoices.delivery-challan'] as $routeName) {
            $this->actingAs($user)->get(route($routeName, $invoice))
                ->assertOk()
                ->assertSee('Customer-visible setup note.')
                ->assertDontSee('Office-only margin note.');
        }
    }

    public function test_bill_uses_default_payment_note_and_invoice_override(): void
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

        $this->actingAs($user)->put(route('invoices.payment-note-default.update'), [
            'payment_note' => 'Default office payment instruction.',
        ])->assertRedirect(route('invoices.payment-note-default.edit'));

        $this->assertSame('Default office payment instruction.', AppSetting::value('invoice_payment_note'));

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_name' => 'Router',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();

        $this->actingAs($user)->get(route('invoices.challan', $invoice))
            ->assertOk()
            ->assertSee('Default office payment instruction.');

        $this->actingAs($user)->put(route('invoices.update', $invoice), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_name' => 'Router',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
            'payment_note' => 'Special note for this invoice only.',
        ])->assertRedirect();

        $this->actingAs($user)->get(route('invoices.challan', $invoice->refresh()))
            ->assertOk()
            ->assertSee('Special note for this invoice only.')
            ->assertDontSee('Default office payment instruction.');
    }
}
