<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAssetAssignment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InHouseUseTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_issued_to_employee_from_new_stock_with_serials(): void
    {
        [$user, $employee, $product, $warehouse] = $this->inventoryFixture();

        $this->actingAs($user)->get(route('in-house-use.index'))
            ->assertOk()
            ->assertSee('Create In-house Issue')
            ->assertSee('Employee & Handover', false)
            ->assertSee('Issue Items')
            ->assertSee('Add Item')
            ->assertSee(route('in-house-use.report.employees'), false)
            ->assertSee(route('in-house-use.report.used-stock'), false)
            ->assertSee(route('in-house-use.report.history'), false)
            ->assertSee($employee->name)
            ->assertSee($product->name)
            ->assertSee('SER-001');

        $this->actingAs($user)->post(route('in-house-use.store'), [
            'employee_id' => $employee->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'source_condition' => 'new',
            'quantity' => 2,
            'serial_numbers' => 'SER-001',
            'serialless_quantity' => 1,
            'assigned_at' => '2026-07-15',
            'purpose' => 'Office network desk',
        ])->assertRedirect();

        $assignment = EmployeeAssetAssignment::firstOrFail();
        $this->assertSame(1, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('employee_asset_assignments', [
            'id' => $assignment->id,
            'employee_id' => $employee->id,
            'product_id' => $product->id,
            'source_condition' => 'new',
            'quantity' => 2,
            'serialless_quantity' => 1,
            'serial_numbers' => 'SER-001',
        ]);
        $this->assertDatabaseHas('product_serials', [
            'serial_number' => 'SER-001',
            'status' => 'used',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'use',
            'quantity' => 2,
            'reference_no' => 'IHA-'.$assignment->id,
        ]);

        $this->actingAs($user)->get(route('in-house-use.show', $assignment))
            ->assertOk()
            ->assertSee('Currently Holding')
            ->assertSee('Receive Return');
    }

    public function test_partial_and_full_returns_enter_separate_used_stock(): void
    {
        [$user, $employee, $product, $warehouse] = $this->inventoryFixture();
        $this->actingAs($user)->post(route('in-house-use.store'), [
            'employee_id' => $employee->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'source_condition' => 'new',
            'quantity' => 3,
            'serial_numbers' => 'SER-001, SER-002',
            'serialless_quantity' => 1,
            'assigned_at' => '2026-07-15',
        ])->assertRedirect();
        $assignment = EmployeeAssetAssignment::firstOrFail();

        $this->actingAs($user)->post(route('in-house-use.returns.store', $assignment), [
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'serial_numbers' => 'SER-001',
            'serialless_quantity' => 0,
            'returned_at' => '2026-07-16',
            'note' => 'Working condition',
        ])->assertRedirect(route('in-house-use.show', $assignment));

        $this->assertDatabaseHas('used_product_warehouse_stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('product_serials', [
            'serial_number' => 'SER-001',
            'status' => 'used_in_stock',
            'warehouse_id' => $warehouse->id,
        ]);
        $this->assertSame(2, $assignment->fresh()->outstandingQuantity());
        $this->assertSame(0, $product->refresh()->stock_quantity, 'Returned used stock must not become new/saleable stock.');

        $this->actingAs($user)->post(route('in-house-use.returns.store', $assignment), [
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'serial_numbers' => 'SER-002',
            'serialless_quantity' => 1,
            'returned_at' => '2026-07-17',
        ])->assertRedirect(route('in-house-use.show', $assignment));

        $this->assertDatabaseHas('used_product_warehouse_stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
        ]);
        $this->assertSame(0, $assignment->fresh()->outstandingQuantity());
        $this->actingAs($user)->get(route('in-house-use.report.used-stock'))
            ->assertOk()
            ->assertSee('Returned Used Stock Report')
            ->assertSee($product->name)
            ->assertSee('SER-001 to SER-002');
    }

    public function test_multiple_products_can_be_issued_from_one_invoice_style_form(): void
    {
        [$user, $employee, $product, $warehouse] = $this->inventoryFixture();
        $cable = Product::create([
            'name' => 'Office Cable',
            'sku' => 'OFFICE-CABLE-001',
            'track_inventory' => true,
            'track_serial_numbers' => false,
            'product_type' => 'consumable',
            'purchase_price' => 10,
            'sale_price' => 15,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
        ]);

        $this->actingAs($user)->post(route('in-house-use.store'), [
            'employee_id' => $employee->id,
            'assigned_at' => '2026-07-15',
            'purpose' => 'New branch setup',
            'items' => [
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'source_condition' => 'new',
                    'quantity' => 1,
                    'serial_numbers' => 'SER-001',
                    'serialless_quantity' => 0,
                ],
                [
                    'product_id' => $cable->id,
                    'warehouse_id' => $warehouse->id,
                    'source_condition' => 'new',
                    'quantity' => 3,
                ],
            ],
        ])->assertRedirect(route('in-house-use.report.employees', ['employee_id' => $employee->id]));

        $this->assertDatabaseCount('employee_asset_assignments', 2);
        $this->assertSame(2, $product->refresh()->stock_quantity);
        $this->assertSame(7, $cable->refresh()->stock_quantity);

        $this->actingAs($user)->get(route('in-house-use.report.employees', ['employee_id' => $employee->id]))
            ->assertOk()
            ->assertSee('Employee Asset Report')
            ->assertSee('Office ONU')
            ->assertSee('Office Cable');
        $this->actingAs($user)->get(route('in-house-use.report.history'))
            ->assertOk()
            ->assertSee('In-house Issue / Return History')
            ->assertSee('New branch setup');
    }

    public function test_returned_used_stock_can_be_reissued_to_another_employee(): void
    {
        [$user, $employee, $product, $warehouse] = $this->inventoryFixture();
        $secondEmployee = Employee::create(['name' => 'Second Technician', 'status' => 'active']);

        $this->actingAs($user)->post(route('in-house-use.store'), [
            'employee_id' => $employee->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'source_condition' => 'new',
            'quantity' => 1,
            'serial_numbers' => 'SER-001',
            'serialless_quantity' => 0,
            'assigned_at' => '2026-07-15',
        ])->assertRedirect();
        $firstAssignment = EmployeeAssetAssignment::firstOrFail();
        $newStockAfterFirstIssue = $product->refresh()->stock_quantity;

        $this->actingAs($user)->post(route('in-house-use.returns.store', $firstAssignment), [
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'serial_numbers' => 'SER-001',
            'serialless_quantity' => 0,
            'returned_at' => '2026-07-16',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('in-house-use.store'), [
            'employee_id' => $secondEmployee->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'source_condition' => 'used',
            'quantity' => 1,
            'serial_numbers' => 'SER-001',
            'serialless_quantity' => 0,
            'assigned_at' => '2026-07-17',
        ])->assertRedirect();

        $this->assertDatabaseHas('employee_asset_assignments', [
            'employee_id' => $secondEmployee->id,
            'product_id' => $product->id,
            'source_condition' => 'used',
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('used_product_warehouse_stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 0,
        ]);
        $this->assertDatabaseHas('product_serials', ['serial_number' => 'SER-001', 'status' => 'used']);
        $this->assertSame($newStockAfterFirstIssue, $product->refresh()->stock_quantity);
    }

    public function test_an_asset_cannot_be_returned_more_than_once(): void
    {
        [$user, $employee, $product, $warehouse] = $this->inventoryFixture();
        $this->actingAs($user)->post(route('in-house-use.store'), [
            'employee_id' => $employee->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'source_condition' => 'new',
            'quantity' => 1,
            'serial_numbers' => 'SER-001',
            'serialless_quantity' => 0,
            'assigned_at' => '2026-07-15',
        ]);
        $assignment = EmployeeAssetAssignment::firstOrFail();
        $returnData = [
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'serial_numbers' => 'SER-001',
            'serialless_quantity' => 0,
            'returned_at' => '2026-07-16',
        ];

        $this->actingAs($user)->post(route('in-house-use.returns.store', $assignment), $returnData)->assertRedirect();
        $this->actingAs($user)->from(route('in-house-use.show', $assignment))
            ->post(route('in-house-use.returns.store', $assignment), $returnData)
            ->assertRedirect(route('in-house-use.show', $assignment))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('employee_asset_returns', 1);
        $this->assertDatabaseHas('used_product_warehouse_stocks', ['product_id' => $product->id, 'quantity' => 1]);
    }

    private function inventoryFixture(): array
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_products')->firstOrFail());
        $employee = Employee::create(['name' => 'Field Technician', 'designation' => 'Technician', 'status' => 'active']);
        $warehouse = Warehouse::where('is_default', true)->firstOrFail();
        $product = Product::create([
            'name' => 'Office ONU',
            'sku' => 'OFFICE-ONU-001',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'product_type' => 'serial_stock',
            'purchase_price' => 800,
            'sale_price' => 1000,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
        ]);

        foreach (['SER-001', 'SER-002'] as $serialNumber) {
            ProductSerial::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'serial_number' => $serialNumber,
                'status' => 'in_stock',
            ]);
        }

        return [$user, $employee, $product, $warehouse];
    }
}
