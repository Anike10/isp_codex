<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_can_be_transferred_between_warehouses_without_changing_total_inventory(): void
    {
        $user = $this->inventoryUser();
        $main = Warehouse::where('is_default', true)->firstOrFail();
        $branch = Warehouse::create(['name' => 'Branch Warehouse', 'code' => 'BRANCH']);
        $product = $this->product(['stock_quantity' => 10]);

        $this->actingAs($user)->post(route('warehouse-transfers.store'), [
            'product_id' => $product->id,
            'from_warehouse_id' => $main->id,
            'to_warehouse_id' => $branch->id,
            'quantity' => 4,
            'reason' => 'Branch replenishment',
        ])->assertRedirect(route('warehouses.show', $branch));

        $this->assertSame(10, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id' => $product->id, 'warehouse_id' => $main->id, 'quantity' => 6]);
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id' => $product->id, 'warehouse_id' => $branch->id, 'quantity' => 4]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $main->id,
            'related_warehouse_id' => $branch->id,
            'type' => 'transfer_out',
            'quantity' => 4,
            'balance_before' => 10,
            'balance_after' => 6,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $branch->id,
            'related_warehouse_id' => $main->id,
            'type' => 'transfer_in',
            'quantity' => 4,
            'balance_before' => 0,
            'balance_after' => 4,
        ]);
        $this->assertSame(
            StockMovement::where('type', 'transfer_out')->value('reference_no'),
            StockMovement::where('type', 'transfer_in')->value('reference_no'),
        );

        $this->actingAs($user)->get(route('warehouse-movements.index', ['warehouse_id' => $branch->id]))
            ->assertOk()
            ->assertSee('Branch replenishment')
            ->assertSee('TRANSFER IN');
    }

    public function test_serial_tracked_transfer_moves_selected_serials_and_serialless_stock(): void
    {
        $user = $this->inventoryUser();
        $main = Warehouse::where('is_default', true)->firstOrFail();
        $branch = Warehouse::create(['name' => 'Service Center', 'code' => 'SERVICE']);
        $product = $this->product([
            'sku' => 'ONU-WH-001',
            'track_serial_numbers' => true,
            'product_type' => 'serial_stock',
            'stock_quantity' => 4,
        ]);

        foreach (['ONU001', 'ONU002', 'ONU003'] as $number) {
            ProductSerial::create([
                'product_id' => $product->id,
                'warehouse_id' => $main->id,
                'serial_number' => $number,
                'status' => 'in_stock',
            ]);
        }

        $this->actingAs($user)->post(route('warehouse-transfers.store'), [
            'product_id' => $product->id,
            'from_warehouse_id' => $main->id,
            'to_warehouse_id' => $branch->id,
            'quantity' => 3,
            'serial_numbers' => 'ONU001-ONU002',
            'serialless_quantity' => 1,
        ])->assertRedirect(route('warehouses.show', $branch));

        $this->assertDatabaseHas('product_serials', ['product_id' => $product->id, 'serial_number' => 'ONU001', 'warehouse_id' => $branch->id]);
        $this->assertDatabaseHas('product_serials', ['product_id' => $product->id, 'serial_number' => 'ONU002', 'warehouse_id' => $branch->id]);
        $this->assertDatabaseHas('product_serials', ['product_id' => $product->id, 'serial_number' => 'ONU003', 'warehouse_id' => $main->id]);
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id' => $product->id, 'warehouse_id' => $main->id, 'quantity' => 1]);
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id' => $product->id, 'warehouse_id' => $branch->id, 'quantity' => 3]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'transfer_out',
            'serial_numbers' => 'ONU001, ONU002',
            'serialless_quantity' => 1,
        ]);

        $this->actingAs($user)->get(route('warehouse-transfers.create'))
            ->assertOk()
            ->assertSee('ONU003')
            ->assertSee('Available Serials in Source Warehouse');
    }

    public function test_transfer_larger_than_source_stock_is_rejected_without_partial_movements(): void
    {
        $user = $this->inventoryUser();
        $main = Warehouse::where('is_default', true)->firstOrFail();
        $branch = Warehouse::create(['name' => 'Small Branch', 'code' => 'SMALL']);
        $product = $this->product(['stock_quantity' => 2]);

        $this->actingAs($user)->from(route('warehouse-transfers.create'))->post(route('warehouse-transfers.store'), [
            'product_id' => $product->id,
            'from_warehouse_id' => $main->id,
            'to_warehouse_id' => $branch->id,
            'quantity' => 3,
        ])->assertRedirect(route('warehouse-transfers.create'))->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id' => $product->id, 'warehouse_id' => $main->id, 'quantity' => 2]);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id, 'type' => 'transfer_out']);
    }

    public function test_multiple_products_transfer_together_with_one_reference_and_atomic_rollback(): void
    {
        $user = $this->inventoryUser();
        $main = Warehouse::where('is_default', true)->firstOrFail();
        $branch = Warehouse::create(['name' => 'Batch Branch', 'code' => 'BATCH']);
        $router = $this->product(['sku' => 'ROUTER-BATCH', 'stock_quantity' => 8]);
        $onu = $this->product([
            'sku' => 'ONU-BATCH',
            'stock_quantity' => 3,
            'track_serial_numbers' => true,
            'product_type' => 'serial_stock',
        ]);
        foreach (['BATCH001', 'BATCH002', 'BATCH003'] as $number) {
            ProductSerial::create([
                'product_id' => $onu->id,
                'warehouse_id' => $main->id,
                'serial_number' => $number,
                'status' => 'in_stock',
            ]);
        }

        $this->actingAs($user)->post(route('warehouse-transfers.store'), [
            'from_warehouse_id' => $main->id,
            'to_warehouse_id' => $branch->id,
            'reason' => 'Combined shipment',
            'items' => [
                ['product_id' => $router->id, 'quantity' => 3],
                ['product_id' => $onu->id, 'quantity' => 2, 'serial_numbers' => 'BATCH001, BATCH002'],
            ],
        ])->assertRedirect(route('warehouses.show', $branch));

        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id' => $router->id, 'warehouse_id' => $branch->id, 'quantity' => 3]);
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id' => $onu->id, 'warehouse_id' => $branch->id, 'quantity' => 2]);
        $this->assertSame(1, StockMovement::where('reason', 'Combined shipment')->distinct()->count('reference_no'));
        $this->assertSame(4, StockMovement::where('reason', 'Combined shipment')->count());

        $beforeRouterStock = $router->warehouseStocks()->where('warehouse_id', $main->id)->value('quantity');
        $this->actingAs($user)->from(route('warehouse-transfers.create'))->post(route('warehouse-transfers.store'), [
            'from_warehouse_id' => $main->id,
            'to_warehouse_id' => $branch->id,
            'items' => [
                ['product_id' => $router->id, 'quantity' => 1],
                ['product_id' => $onu->id, 'quantity' => 99, 'serialless_quantity' => 99],
            ],
        ])->assertRedirect(route('warehouse-transfers.create'))->assertSessionHasErrors('items');

        $this->assertSame($beforeRouterStock, $router->warehouseStocks()->where('warehouse_id', $main->id)->value('quantity'));
    }

    private function inventoryUser(): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());

        return $user;
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Warehouse Product',
            'sku' => 'WH-001',
            'brand' => 'Generic',
            'track_inventory' => true,
            'track_serial_numbers' => false,
            'product_type' => 'stock',
            'purchase_price' => 100,
            'sale_price' => 150,
            'stock_quantity' => 0,
            'low_stock_alert' => 1,
        ], $overrides));
    }
}
