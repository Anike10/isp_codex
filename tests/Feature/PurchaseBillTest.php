<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseBillTest extends TestCase
{
    use RefreshDatabase;

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
                    'warranty_months' => 12,
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
    }

    public function test_own_use_stock_movement_reduces_available_stock(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $product = Product::create([
            'name' => 'Patch Cord',
            'sku' => 'PC-001',
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
