<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
use App\Models\PurchaseBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_brand_category_and_subcategory(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $category = ProductCategory::create(['name' => 'Router']);
        $subcategory = ProductCategory::create(['parent_id' => $category->id, 'name' => 'Core Router']);

        $this->actingAs($user)->post(route('products.store'), [
            'name' => 'CCR Router',
            'sku' => 'CCR-001',
            'barcode' => '8991001001',
            'brand' => 'MikroTik',
            'product_category_id' => $subcategory->id,
            'track_serial_numbers' => '1',
            'warranty_days' => 365,
            'purchase_price' => 10000,
            'sale_price' => 12500,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'CCR-001',
            'barcode' => '8991001001',
            'brand' => 'MikroTik',
            'product_category_id' => $subcategory->id,
            'track_serial_numbers' => true,
            'warranty_days' => 365,
            'category' => 'Router',
            'subcategory' => 'Core Router',
        ]);
    }

    public function test_non_stock_product_can_be_created_without_inventory_tracking(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());

        $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Installation Charge',
            'sku' => 'SVC-001',
            'brand' => 'Service',
            'track_inventory' => '0',
            'track_serial_numbers' => '1',
            'purchase_price' => 0,
            'sale_price' => 500,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'SVC-001',
            'track_inventory' => false,
            'track_serial_numbers' => false,
            'stock_quantity' => 0,
            'low_stock_alert' => 0,
        ]);
    }

    public function test_product_index_can_filter_by_brand_category_and_subcategory(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $routerCategory = ProductCategory::create(['name' => 'Router']);
        $coreRouterCategory = ProductCategory::create(['parent_id' => $routerCategory->id, 'name' => 'Core Router']);
        $cableCategory = ProductCategory::create(['name' => 'Cable']);
        $utpCableCategory = ProductCategory::create(['parent_id' => $cableCategory->id, 'name' => 'UTP Cable']);
        Product::create([
            'name' => 'CCR Router',
            'sku' => 'CCR-001',
            'brand' => 'MikroTik',
            'product_category_id' => $coreRouterCategory->id,
            'category' => 'Router',
            'subcategory' => 'Core Router',
            'purchase_price' => 10000,
            'sale_price' => 12500,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ]);
        Product::create([
            'name' => 'CAT6 Cable',
            'sku' => 'CBL-001',
            'brand' => 'Generic',
            'product_category_id' => $utpCableCategory->id,
            'category' => 'Cable',
            'subcategory' => 'UTP Cable',
            'purchase_price' => 15,
            'sale_price' => 25,
            'stock_quantity' => 100,
            'low_stock_alert' => 20,
        ]);

        $this->actingAs($user)->get(route('products.index', [
            'brand' => 'MikroTik',
            'product_category_id' => $routerCategory->id,
        ]))
            ->assertOk()
            ->assertSee('CCR Router')
            ->assertSee(route('products.edit', Product::where('sku', 'CCR-001')->firstOrFail()), false)
            ->assertDontSee('CAT6 Cable');
    }

    public function test_product_index_can_trace_by_serial_number(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Serial Buyer',
            'phone' => '01711111111',
            'connection_id' => 'SB-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $matchedProduct = Product::create([
            'name' => 'Searchable ONU',
            'sku' => 'ONU-SERIAL-SEARCH',
            'brand' => 'BDCOM',
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ]);
        Product::create([
            'name' => 'Other Router',
            'sku' => 'RTR-SERIAL-SEARCH',
            'brand' => 'MikroTik',
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'bill_no' => 'PB-SERIAL-TRACE-001',
            'purchase_date' => '2026-07-14',
            'items' => [
                [
                    'product_id' => $matchedProduct->id,
                    'quantity' => 1,
                    'unit_price' => 900,
                    'serial_numbers' => 'SERIAL-FIND-001',
                ],
            ],
        ])->assertRedirect(route('purchase-bills.index'));

        $purchaseBill = PurchaseBill::where('bill_no', 'PB-SERIAL-TRACE-001')->firstOrFail();

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-07',
            'items' => [
                [
                    'product_id' => $matchedProduct->id,
                    'product_name' => $matchedProduct->name,
                    'quantity' => 1,
                    'unit_price' => 1200,
                    'serial_numbers' => 'SERIAL-FIND-001',
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();

        $this->actingAs($user)->get(route('products.index', ['serial_search' => 'SERIAL-FIND-001']))
            ->assertOk()
            ->assertSee('Serial Trace')
            ->assertSee('SERIAL-FIND-001')
            ->assertSee('Searchable ONU')
            ->assertSee(route('purchase-bills.show', $purchaseBill), false)
            ->assertSee('PB-SERIAL-TRACE-001')
            ->assertSee(route('invoices.show', $invoice), false)
            ->assertSee($invoice->invoice_no)
            ->assertSee('Serial Buyer')
            ->assertDontSee('Other Router');
    }

    public function test_product_can_be_edited_without_changing_stock_quantity(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $category = ProductCategory::create(['name' => 'Router']);
        $product = Product::create([
            'name' => 'Old Router',
            'sku' => 'OLD-ROUTER',
            'barcode' => 'OLD-BARCODE',
            'brand' => 'Old Brand',
            'product_category_id' => $category->id,
            'category' => 'Router',
            'track_inventory' => true,
            'track_serial_numbers' => false,
            'product_type' => 'stock',
            'purchase_price' => 100,
            'sale_price' => 150,
            'stock_quantity' => 7,
            'low_stock_alert' => 2,
        ]);

        $this->actingAs($user)->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Edit Product')
            ->assertSee('OLD-ROUTER')
            ->assertSee('Use Move Stock or Purchase Bill to change stock.');

        $this->actingAs($user)->put(route('products.update', $product), [
            'name' => 'Updated Router',
            'sku' => 'NEW-ROUTER',
            'barcode' => 'NEW-BARCODE',
            'brand' => 'New Brand',
            'product_category_id' => $category->id,
            'product_type' => 'stock',
            'track_inventory' => '1',
            'track_serial_numbers' => '0',
            'warranty_days' => 365,
            'purchase_price' => 110,
            'sale_price' => 175,
            'stock_quantity' => 99,
            'low_stock_alert' => 3,
        ])->assertRedirect(route('products.show', $product));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Router',
            'sku' => 'NEW-ROUTER',
            'barcode' => 'NEW-BARCODE',
            'brand' => 'New Brand',
            'stock_quantity' => 7,
            'low_stock_alert' => 3,
            'warranty_days' => 365,
        ]);
    }

    public function test_product_edit_blocks_disabling_inventory_when_stock_exists(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'Tracked Router',
            'sku' => 'TRACKED-ROUTER',
            'brand' => 'Brand',
            'track_inventory' => true,
            'product_type' => 'stock',
            'purchase_price' => 100,
            'sale_price' => 150,
            'stock_quantity' => 2,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->from(route('products.edit', $product))->put(route('products.update', $product), [
            'name' => 'Tracked Router',
            'sku' => 'TRACKED-ROUTER',
            'brand' => 'Brand',
            'product_type' => 'service',
            'track_inventory' => '0',
            'track_serial_numbers' => '0',
            'purchase_price' => 100,
            'sale_price' => 150,
        ])->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors('track_inventory');

        $this->assertTrue($product->refresh()->track_inventory);
        $this->assertSame(2, $product->stock_quantity);
    }

    public function test_non_serial_product_conversion_requires_existing_stock_serial_split(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'Plain ONU',
            'sku' => 'PLAIN-ONU',
            'brand' => 'Brand',
            'track_inventory' => true,
            'track_serial_numbers' => false,
            'product_type' => 'stock',
            'purchase_price' => 100,
            'sale_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->from(route('products.edit', $product))->put(route('products.update', $product), [
            'name' => 'Plain ONU',
            'sku' => 'PLAIN-ONU',
            'brand' => 'Brand',
            'product_type' => 'serial_stock',
            'track_inventory' => '1',
            'track_serial_numbers' => '1',
            'purchase_price' => 100,
            'sale_price' => 150,
            'low_stock_alert' => 1,
            'serial_conversion_serial_numbers' => 'ONU001, ONU002',
            'serial_conversion_serialless_quantity' => 2,
        ])->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors('serial_conversion');

        $this->assertFalse($product->refresh()->track_serial_numbers);
    }

    public function test_non_serial_product_can_be_converted_with_serial_and_serialless_split(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'Plain Router',
            'sku' => 'PLAIN-ROUTER',
            'brand' => 'Brand',
            'track_inventory' => true,
            'track_serial_numbers' => false,
            'product_type' => 'stock',
            'purchase_price' => 100,
            'sale_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Existing Stock Serial Split')
            ->assertSee('Serial-less Qty');

        $this->actingAs($user)->put(route('products.update', $product), [
            'name' => 'Plain Router',
            'sku' => 'PLAIN-ROUTER',
            'brand' => 'Brand',
            'product_type' => 'serial_stock',
            'track_inventory' => '1',
            'track_serial_numbers' => '1',
            'purchase_price' => 100,
            'sale_price' => 150,
            'low_stock_alert' => 1,
            'serial_conversion_serial_numbers' => 'RTR001, RTR002',
            'serial_conversion_serialless_quantity' => 3,
        ])->assertRedirect(route('products.show', $product));

        $this->assertTrue($product->refresh()->track_serial_numbers);
        $this->assertSame(5, $product->stock_quantity);
        foreach (['RTR001', 'RTR002'] as $serialNumber) {
            $this->assertDatabaseHas('product_serials', [
                'product_id' => $product->id,
                'serial_number' => $serialNumber,
                'status' => 'in_stock',
            ]);
        }

        $this->actingAs($user)->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('In House: 2')
            ->assertSee('Serial-less: 3');
    }

    public function test_own_use_must_go_through_employee_assignment_workflow(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-USE-001',
            'brand' => 'BDCOM',
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
        ]);

        foreach (['ONU001', 'ONU002', 'ONU003'] as $serialNumber) {
            ProductSerial::create([
                'product_id' => $product->id,
                'serial_number' => $serialNumber,
                'status' => 'in_stock',
            ]);
        }
        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'ONU999',
            'status' => 'out',
        ]);

        $this->actingAs($user)->get(route('products.index'))
            ->assertOk()
            ->assertSee(route('in-house-use.index'), false)
            ->assertSee('Assign Employee')
            ->assertSee('Serials / range')
            ->assertSee('Serial-less Qty')
            ->assertSee('In-stock serials (3)')
            ->assertSee('available-serial-option')
            ->assertSee('ONU001')
            ->assertDontSee('ONU999')
            ->assertSee('data-stock="3"', false)
            ->assertSee('syncStockForm');

        $this->actingAs($user)->post(route('products.stock', $product), [
            'type' => 'use',
            'quantity' => 2,
            'serial_numbers' => 'ONU001-ONU002',
            'reason' => 'Customer installation',
        ])->assertSessionHasErrors('type');

        $this->assertSame(3, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('product_serials', [
            'product_id' => $product->id,
            'serial_number' => 'ONU001',
            'status' => 'in_stock',
        ]);
        $this->assertDatabaseCount('employee_asset_assignments', 0);
    }

    public function test_serial_tracked_stock_movement_can_record_serialless_quantity(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-SERIALLESS-USE',
            'brand' => 'BDCOM',
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
        ]);

        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'ONU001',
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->post(route('products.stock', $product), [
            'type' => 'out',
            'quantity' => 3,
            'serial_numbers' => 'ONU001',
            'serialless_quantity' => 2,
            'reason' => 'Damaged stock out',
        ])->assertRedirect(route('products.index'));

        $this->assertSame(0, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
            'serialless_quantity' => 2,
            'reason' => 'Damaged stock out',
        ]);

        $this->actingAs($user)->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Serial-less Qty')
            ->assertSee('2');
    }

    public function test_serial_tracked_stock_movement_requires_serial_or_serialless_count_for_every_piece(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-SERIALLESS-MISSING',
            'brand' => 'BDCOM',
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
        ]);

        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'ONU001',
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->post(route('products.stock', $product), [
            'type' => 'out',
            'quantity' => 3,
            'serial_numbers' => 'ONU001',
            'serialless_quantity' => 1,
            'reason' => 'Damaged stock out',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(3, $product->refresh()->stock_quantity);
    }

    public function test_product_show_links_purchase_bill_serials_and_stock_references(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-PRODUCT-LINK-001',
            'brand' => 'BDCOM',
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 0,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'bill_no' => 'PB-PRODUCT-LINK-001',
            'purchase_date' => '2026-07-14',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 900,
                    'serial_numbers' => 'LINK001',
                ],
            ],
        ])->assertRedirect(route('purchase-bills.index'));

        $purchaseBill = PurchaseBill::where('bill_no', 'PB-PRODUCT-LINK-001')->firstOrFail();

        $this->actingAs($user)->get(route('products.show', $product))
            ->assertOk()
            ->assertSee(route('purchase-bills.show', $purchaseBill), false)
            ->assertSee('PB-PRODUCT-LINK-001');
    }
}
