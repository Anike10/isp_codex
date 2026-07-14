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

        $invoice->items()->firstOrFail()->update([
            'serial_numbers' => "ONU-SN-001\nONU-SN-002",
        ]);

        $this->actingAs($user)->get(route('invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('ONU-SN-001 to ONU-SN-002');
    }

    public function test_invoice_index_can_search_by_sold_serial_number(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Serial Customer',
            'phone' => '01711111111',
            'connection_id' => 'SC-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'Searchable ONU',
            'sku' => 'INV-SERIAL-PRODUCT',
            'brand' => 'BDCOM',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ]);
        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'INV-SERIAL-FIND-001',
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-07',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => 1200,
                    'serial_numbers' => 'INV-SERIAL-FIND-001',
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();

        $this->actingAs($user)->get(route('invoices.index', ['search' => 'INV-SERIAL-FIND-001']))
            ->assertOk()
            ->assertSee($invoice->invoice_no)
            ->assertSee('Serial Customer');
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

    public function test_invoice_create_page_shows_serialless_stock_count_for_serial_products(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $product = Product::create([
            'name' => 'Monitor',
            'sku' => 'MON-SERIALLESS-STOCK',
            'brand' => 'HP',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 700,
            'sale_price' => 1000,
            'stock_quantity' => 4,
            'low_stock_alert' => 1,
        ]);

        foreach (['101', '102', '103'] as $serialNumber) {
            ProductSerial::create([
                'product_id' => $product->id,
                'serial_number' => $serialNumber,
                'status' => 'in_stock',
            ]);
        }

        $this->actingAs($user)->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('"serialless_stock":1', false)
            ->assertSee('Serial-less Stock: 0');
    }

    public function test_invoice_serial_options_are_ordered_by_earliest_warranty(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $product = Product::create([
            'name' => 'Monitor',
            'sku' => 'MON-WARRANTY-ORDER',
            'brand' => 'HP',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 700,
            'sale_price' => 1000,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
        ]);

        foreach ([
            ['serial_number' => 'LATE-WARRANTY', 'warranty_until' => '2027-07-14'],
            ['serial_number' => 'EARLY-WARRANTY', 'warranty_until' => '2027-01-14'],
            ['serial_number' => 'NO-WARRANTY', 'warranty_until' => null],
        ] as $row) {
            ProductSerial::create([
                'product_id' => $product->id,
                'serial_number' => $row['serial_number'],
                'warranty_until' => $row['warranty_until'],
                'status' => 'in_stock',
            ]);
        }

        $response = $this->actingAs($user)->get(route('invoices.create'))
            ->assertOk();

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'LATE-WARRANTY'), strpos($content, 'EARLY-WARRANTY'));
        $this->assertLessThan(strpos($content, 'NO-WARRANTY'), strpos($content, 'LATE-WARRANTY'));
    }

    public function test_invoice_sale_records_serialless_quantity_on_details_but_not_print(): void
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
            'sku' => 'ONU-MIXED-SALE',
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
            'serial_number' => 'ONU-MIXED-001',
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 3,
                    'unit_price' => 1200,
                    'serial_numbers' => 'ONU-MIXED-001',
                    'serialless_quantity' => 2,
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
            'serialless_quantity' => 2,
            'serial_numbers' => 'ONU-MIXED-001',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
            'serialless_quantity' => 2,
            'reference_no' => $invoice->invoice_no,
        ]);

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Serial-less Qty')
            ->assertSee('ONU-MIXED-001');

        $this->actingAs($user)->get(route('invoices.invoice', $invoice))
            ->assertOk()
            ->assertSee('Serial:')
            ->assertSee('ONU-MIXED-001')
            ->assertDontSee('Serial-less Qty');
    }

    public function test_invoice_sale_requires_serial_or_serialless_count_for_every_piece(): void
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
            'sku' => 'ONU-MISSING-SALE',
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
            'serial_number' => 'ONU-MISSING-001',
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 3,
                    'unit_price' => 1200,
                    'serial_numbers' => 'ONU-MISSING-001',
                    'serialless_quantity' => 1,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertSessionHasErrors([
            'items' => 'ONU Device is serial-tracked. Select serials or enter Serial-less Qty for all 3 unit(s). Current count: 1 serial(s) + 1 serial-less.',
        ]);

        $this->assertSame(3, $product->refresh()->stock_quantity);
    }

    public function test_invoice_text_item_can_store_serials_when_serial_checkbox_is_enabled(): void
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
            'billing_month' => '2026-07',
            'items' => [
                [
                    'product_name' => 'Typed ONU',
                    'quantity' => 2,
                    'unit_price' => 1200,
                    'track_serial_numbers' => '1',
                    'serial_numbers' => 'TYPED-ONU-001',
                    'serialless_quantity' => 1,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('invoice_items', [
            'product_id' => null,
            'product_name' => 'Typed ONU',
            'quantity' => 2,
            'serial_numbers' => 'TYPED-ONU-001',
            'serialless_quantity' => 1,
        ]);
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

        $this->actingAs($user)->get(route('invoices.invoice', $invoice))
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

        foreach (['invoices.invoice', 'invoices.quotation', 'invoices.delivery-challan'] as $routeName) {
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

        $this->actingAs($user)->get(route('invoices.invoice', $invoice))
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

        $this->actingAs($user)->get(route('invoices.invoice', $invoice->refresh()))
            ->assertOk()
            ->assertSee('Special note for this invoice only.')
            ->assertDontSee('Default office payment instruction.');
    }
}
