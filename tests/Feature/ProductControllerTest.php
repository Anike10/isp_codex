<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
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

        $this->actingAs($user)->post(route('products.store'), [
            'name' => 'CCR Router',
            'sku' => 'CCR-001',
            'brand' => 'MikroTik',
            'category' => 'Router',
            'subcategory' => 'Core Router',
            'purchase_price' => 10000,
            'sale_price' => 12500,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'CCR-001',
            'brand' => 'MikroTik',
            'category' => 'Router',
            'subcategory' => 'Core Router',
        ]);
    }

    public function test_product_index_can_filter_by_brand_category_and_subcategory(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        Product::create([
            'name' => 'CCR Router',
            'sku' => 'CCR-001',
            'brand' => 'MikroTik',
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
            'category' => 'Cable',
            'subcategory' => 'UTP Cable',
            'purchase_price' => 15,
            'sale_price' => 25,
            'stock_quantity' => 100,
            'low_stock_alert' => 20,
        ]);

        $this->actingAs($user)->get(route('products.index', [
            'brand' => 'MikroTik',
            'category' => 'Router',
            'subcategory' => 'Core Router',
        ]))
            ->assertOk()
            ->assertSee('CCR Router')
            ->assertDontSee('CAT6 Cable');
    }
}
