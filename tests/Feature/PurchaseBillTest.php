<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
use App\Models\PurchaseBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseBillTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_bill_create_page_renders_with_category_filters(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $category = ProductCategory::create(['name' => 'Network Device']);
        $subcategory = ProductCategory::create(['parent_id' => $category->id, 'name' => 'ONU']);

        Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-001',
            'brand' => 'BDCOM',
            'product_category_id' => $subcategory->id,
            'category' => 'Network Device',
            'subcategory' => 'ONU',
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 0,
            'low_stock_alert' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('purchase-bills.create'))
            ->assertOk()
            ->assertSee('Add Purchase Bill')
            ->assertSee('ONU Device');
    }

    public function test_purchase_bill_adds_stock_and_tracks_serial_warranty(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $vendor = Customer::create([
            'name' => 'Wholesale Shop',
            'phone' => '01733333333',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => false,
            'is_vendor' => true,
        ]);
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-001',
            'brand' => 'BDCOM',
            'category' => 'Network Device',
            'subcategory' => 'ONU',
            'barcode' => 'ONU-BAR-001',
            'track_serial_numbers' => true,
            'warranty_days' => 365,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 0,
            'low_stock_alert' => 2,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'party_id' => $vendor->id,
            'bill_no' => 'PB-TEST-001',
            'purchase_date' => '2026-06-02',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 900,
                    'warranty_days' => 365,
                    'serial_numbers' => "ONU-A1\nONU-A2",
                ],
            ],
        ])->assertRedirect(route('purchase-bills.index'));

        $this->assertSame(2, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('purchase_bills', [
            'bill_no' => 'PB-TEST-001',
            'party_id' => $vendor->id,
            'subtotal' => 1800,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 2,
            'reference_no' => 'PB-TEST-001',
        ]);
        $this->assertSame(2, ProductSerial::where('product_id', $product->id)->count());
        $this->assertDatabaseHas('product_serials', [
            'product_id' => $product->id,
            'serial_number' => 'ONU-A1',
            'warranty_until' => '2027-06-02 00:00:00',
            'status' => 'in_stock',
        ]);
        $purchaseBill = PurchaseBill::where('bill_no', 'PB-TEST-001')->firstOrFail();
        $this->assertDatabaseMissing('record_versions', [
            'versionable_type' => Product::class,
            'versionable_id' => $product->id,
        ]);
        $this->assertDatabaseMissing('record_versions', [
            'versionable_type' => PurchaseBill::class,
            'versionable_id' => $purchaseBill->id,
        ]);
    }

    public function test_purchase_bill_can_create_new_product_from_typed_name(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'bill_no' => 'PB-NEW-PRODUCT-001',
            'purchase_date' => '2026-06-03',
            'items' => [
                [
                    'product_name' => 'Fiber Cutter',
                    'quantity' => 3,
                    'unit_price' => 250,
                    'warranty_days' => 30,
                ],
            ],
        ])->assertRedirect(route('purchase-bills.index'));

        $product = Product::where('name', 'Fiber Cutter')->firstOrFail();

        $this->assertSame(3, $product->stock_quantity);
        $this->assertSame(250.0, (float) $product->purchase_price);
        $this->assertSame(250.0, (float) $product->sale_price);
        $this->assertDatabaseHas('purchase_bill_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 250,
            'total' => 750,
        ]);
    }

    public function test_purchase_bill_can_create_new_vendor_from_typed_name(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'Patch Cord',
            'sku' => 'PC-VENDOR-001',
            'brand' => 'Generic',
            'purchase_price' => 30,
            'sale_price' => 50,
            'stock_quantity' => 0,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'party_name' => 'New Vendor Shop',
            'bill_no' => 'PB-NEW-VENDOR-001',
            'purchase_date' => '2026-06-03',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 30,
                ],
            ],
        ])->assertRedirect(route('purchase-bills.index'));

        $vendor = Customer::where('name', 'New Vendor Shop')->firstOrFail();

        $this->assertFalse($vendor->is_customer);
        $this->assertTrue($vendor->is_vendor);
        $this->assertDatabaseHas('purchase_bills', [
            'bill_no' => 'PB-NEW-VENDOR-001',
            'party_id' => $vendor->id,
        ]);
    }

    public function test_purchase_bill_accepts_serial_ranges_and_updates_quantity_from_serial_count(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-RANGE-001',
            'brand' => 'BDCOM',
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 0,
            'low_stock_alert' => 2,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'bill_no' => 'PB-RANGE-001',
            'purchase_date' => '2026-06-03',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 900,
                    'serial_numbers' => 'ONU001-ONU003, ONU010 থেকে ONU011',
                ],
            ],
        ])->assertRedirect(route('purchase-bills.index'));

        $this->assertSame(5, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('purchase_bills', [
            'bill_no' => 'PB-RANGE-001',
            'subtotal' => 4500,
        ]);
        $this->assertDatabaseHas('purchase_bill_items', [
            'product_id' => $product->id,
            'quantity' => 5,
            'total' => 4500,
        ]);

        foreach (['ONU001', 'ONU002', 'ONU003', 'ONU010', 'ONU011'] as $serialNumber) {
            $this->assertDatabaseHas('product_serials', [
                'product_id' => $product->id,
                'serial_number' => $serialNumber,
                'status' => 'in_stock',
            ]);
        }
    }

    public function test_serial_tracked_purchase_can_record_serialless_quantity(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-MIXED-001',
            'brand' => 'BDCOM',
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 0,
            'low_stock_alert' => 2,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'bill_no' => 'PB-MIXED-001',
            'purchase_date' => '2026-06-19',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 900,
                    'serial_numbers' => 'ONU-MIXED-001',
                    'serialless_quantity' => 2,
                ],
            ],
        ])->assertRedirect(route('purchase-bills.index'));

        $this->assertSame(3, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('purchase_bill_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'serialless_quantity' => 2,
            'total' => 2700,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 3,
            'serialless_quantity' => 2,
            'reference_no' => 'PB-MIXED-001',
        ]);
    }

    public function test_serial_tracked_purchase_requires_serial_or_serialless_count_for_every_piece(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'ONU Device',
            'sku' => 'ONU-MISSING-001',
            'brand' => 'BDCOM',
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 0,
            'low_stock_alert' => 2,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'bill_no' => 'PB-MISSING-001',
            'purchase_date' => '2026-06-19',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 900,
                    'serial_numbers' => 'ONU-MISSING-001',
                    'serialless_quantity' => 1,
                ],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, $product->refresh()->stock_quantity);
    }

    public function test_serial_numbers_require_serial_tracking_enabled(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'Patch Cord',
            'sku' => 'PC-002',
            'brand' => 'Generic',
            'purchase_price' => 30,
            'sale_price' => 50,
            'stock_quantity' => 0,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'bill_no' => 'PB-SERIAL-OFF-001',
            'purchase_date' => '2026-06-02',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 30,
                    'serial_numbers' => 'PC-SN-001',
                ],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('product_serials', [
            'serial_number' => 'PC-SN-001',
        ]);
    }

    public function test_purchase_bill_can_include_non_stock_items_without_stock_movement(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'Installation Charge',
            'sku' => 'SVC-001',
            'brand' => 'Service',
            'track_inventory' => false,
            'purchase_price' => 0,
            'sale_price' => 500,
            'stock_quantity' => 0,
            'low_stock_alert' => 0,
        ]);

        $this->actingAs($user)->post(route('purchase-bills.store'), [
            'bill_no' => 'PB-SERVICE-001',
            'purchase_date' => '2026-06-02',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ])->assertRedirect(route('purchase-bills.index'));

        $this->assertSame(0, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('purchase_bills', [
            'bill_no' => 'PB-SERVICE-001',
            'subtotal' => 500,
        ]);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'reference_no' => 'PB-SERVICE-001',
        ]);
    }

    public function test_own_use_stock_movement_reduces_available_stock(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'Patch Cord',
            'sku' => 'PC-001',
            'brand' => 'Generic',
            'category' => 'Cable',
            'subcategory' => 'Patch Cord',
            'purchase_price' => 30,
            'sale_price' => 50,
            'stock_quantity' => 5,
            'low_stock_alert' => 1,
        ]);

        $this->actingAs($user)->post(route('products.stock', $product), [
            'type' => 'use',
            'quantity' => 2,
            'reason' => 'Used in office setup',
        ])->assertRedirect(route('products.index'));

        $this->assertSame(3, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'use',
            'quantity' => 2,
            'reason' => 'Used in office setup',
        ]);
    }
}
