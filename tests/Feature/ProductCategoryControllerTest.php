<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_category_can_be_added_under_parent(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $parent = ProductCategory::create(['name' => 'Router']);

        $this->actingAs($user)->post(route('product-categories.store'), [
            'parent_id' => $parent->id,
            'name' => 'Core Router',
        ])->assertRedirect(route('product-categories.index'));

        $this->assertDatabaseHas('product_categories', [
            'parent_id' => $parent->id,
            'name' => 'Core Router',
        ]);
    }

    public function test_duplicate_top_level_product_category_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        ProductCategory::create(['name' => 'Router']);

        $this->actingAs($user)->from(route('product-categories.index'))->post(route('product-categories.store'), [
            'name' => 'Router',
        ])
            ->assertRedirect(route('product-categories.index'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('product_categories', 1);
    }
}
