<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
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
            ->assertDontSee('CAT6 Cable');
    }

    public function test_serial_tracked_product_marks_own_use_serials(): void
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
            ->assertSee('Serials / range')
            ->assertSee('Serial-less Qty')
            ->assertSee('In-stock serials (3)')
            ->assertSee('available-serial-option')
            ->assertSee('ONU001')
            ->assertDontSee('ONU999')
            ->assertSee('Available before movement: 3')
            ->assertSee('syncStockForm');

        $this->actingAs($user)->post(route('products.stock', $product), [
            'type' => 'use',
            'quantity' => 2,
            'serial_numbers' => 'ONU001-ONU002',
            'reason' => 'Customer installation',
        ])->assertRedirect(route('products.index'));

        $this->assertSame(1, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('product_serials', [
            'product_id' => $product->id,
            'serial_number' => 'ONU001',
            'status' => 'used',
            'note' => 'Customer installation',
        ]);
        $this->assertDatabaseHas('product_serials', [
            'product_id' => $product->id,
            'serial_number' => 'ONU003',
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('In House: 1')
            ->assertSee('Own Use: 2')
            ->assertSee('Customer installation');
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
            'type' => 'use',
            'quantity' => 3,
            'serial_numbers' => 'ONU001',
            'serialless_quantity' => 2,
            'reason' => 'Office install',
        ])->assertRedirect(route('products.index'));

        $this->assertSame(0, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'use',
            'quantity' => 3,
            'serialless_quantity' => 2,
            'reason' => 'Office install',
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
            'type' => 'use',
            'quantity' => 3,
            'serial_numbers' => 'ONU001',
            'serialless_quantity' => 1,
            'reason' => 'Office install',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(3, $product->refresh()->stock_quantity);
    }
}
