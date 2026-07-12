<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\User;
use App\Models\WarrantyClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAndWarrantyTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_product_invoice_does_not_create_stock_movement(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Service Customer',
            'phone' => '01710000000',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $service = Product::create([
            'name' => 'Router Configuration',
            'sku' => 'SVC-CONFIG',
            'product_type' => 'service',
            'track_inventory' => false,
            'track_serial_numbers' => false,
            'service_guarantee_days' => 7,
            'purchase_price' => 0,
            'sale_price' => 500,
            'stock_quantity' => 0,
            'low_stock_alert' => 0,
        ]);

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-06',
            'items' => [
                [
                    'product_id' => $service->id,
                    'product_name' => $service->name,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect();

        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'product_id' => $service->id,
            'product_type' => 'service',
            'service_guarantee_days' => 7,
            'total' => 500,
        ]);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $service->id,
        ]);
    }

    public function test_warranty_claim_can_be_created_and_replaced(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_warranty_claims')->firstOrFail());
        $user->permissions()->attach(Permission::where('name', 'view_warranty_claims')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Warranty Customer',
            'phone' => '01720000000',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'HP 3200',
            'sku' => 'HP-3200-W',
            'product_type' => 'serial_stock',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'warranty_days' => 365,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ]);
        $soldSerial = ProductSerial::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'serial_number' => '1001',
            'warranty_until' => now()->addYear()->toDateString(),
            'sold_at' => now(),
            'status' => 'sold',
        ]);
        $replacementSerial = ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => '1002',
            'warranty_until' => now()->addYear()->toDateString(),
            'status' => 'in_stock',
        ]);

        $this->actingAs($user)->post(route('warranty-claims.store'), [
            'customer_id' => $customer->id,
            'product_serial_id' => $soldSerial->id,
            'claim_date' => now()->toDateString(),
            'problem_description' => 'No power',
            'action_type' => 'replace',
        ])->assertRedirect();

        $claim = WarrantyClaim::where('product_serial_id', $soldSerial->id)->firstOrFail();

        $this->actingAs($user)->post(route('warranty-claims.replace', $claim), [
            'replacement_product_serial_id' => $replacementSerial->id,
            'resolution_note' => 'Replacement provided',
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('warranty-claims.show', $claim))
            ->assertOk()
            ->assertSee('Work History')
            ->assertSee('Claim Created')
            ->assertSee('Replacement Assigned')
            ->assertSee('Old Serial')
            ->assertSee('New Serial')
            ->assertSee('Replacement provided');

        $this->assertDatabaseHas('warranty_claims', [
            'id' => $claim->id,
            'status' => 'replaced',
            'replacement_product_serial_id' => $replacementSerial->id,
        ]);
        $this->assertDatabaseHas('product_serials', [
            'id' => $soldSerial->id,
            'status' => 'replaced',
        ]);
        $this->assertDatabaseHas('product_serials', [
            'id' => $replacementSerial->id,
            'status' => 'sold',
            'customer_id' => $customer->id,
        ]);
        $this->assertSame(0, $product->refresh()->stock_quantity);
    }

    public function test_warranty_claim_create_page_supports_serial_lookup(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_warranty_claims')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Lookup Customer',
            'phone' => '01730000000',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'Lookup Router',
            'sku' => 'LOOKUP-ROUTER',
            'product_type' => 'serial_stock',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 0,
            'low_stock_alert' => 1,
        ]);

        ProductSerial::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'serial_number' => 'SN-LOOKUP-001',
            'warranty_until' => now()->addYear()->toDateString(),
            'sold_at' => now(),
            'status' => 'sold',
        ]);

        $this->actingAs($user)
            ->get(route('warranty-claims.create'))
            ->assertOk()
            ->assertSee('Serial Number')
            ->assertSee('SN-LOOKUP-001')
            ->assertSee('Lookup Customer')
            ->assertSee('Lookup Router');
    }

    public function test_warranty_manager_can_open_claim_index_and_created_claim_without_view_permission(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_warranty_claims')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Manager Warranty Customer',
            'phone' => '01740000000',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'Manager Router',
            'sku' => 'MGR-ROUTER',
            'product_type' => 'serial_stock',
            'track_inventory' => true,
            'track_serial_numbers' => true,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 0,
            'low_stock_alert' => 1,
        ]);
        $serial = ProductSerial::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'serial_number' => 'SN-MGR-001',
            'warranty_until' => now()->addYear()->toDateString(),
            'sold_at' => now(),
            'status' => 'sold',
        ]);

        $this->actingAs($user)->get(route('warranty-claims.index'))->assertOk();

        $this->actingAs($user)->post(route('warranty-claims.store'), [
            'customer_id' => $customer->id,
            'product_serial_id' => $serial->id,
            'claim_date' => now()->toDateString(),
            'problem_description' => 'Adapter issue',
            'action_type' => 'repair',
        ])->assertRedirect();

        $claim = WarrantyClaim::where('product_serial_id', $serial->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('warranty-claims.show', $claim))
            ->assertOk()
            ->assertSee($claim->claim_no);
    }

    public function test_repair_intake_can_create_service_invoice_for_accounting(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_warranty_claims')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Repair Customer',
            'phone' => '01750000000',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'Repair Router',
            'sku' => 'REP-ROUTER',
            'product_type' => 'service',
            'track_inventory' => false,
            'track_serial_numbers' => false,
            'purchase_price' => 0,
            'sale_price' => 800,
            'stock_quantity' => 0,
            'low_stock_alert' => 0,
        ]);

        $this->actingAs($user)->post(route('warranty-claims.store'), [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'claim_date' => now()->toDateString(),
            'problem_description' => 'Firmware repair and service',
            'action_type' => 'repair',
            'service_charge' => 800,
            'create_service_invoice' => '1',
            'service_note' => 'Router repair service charge',
        ])->assertRedirect();

        $claim = WarrantyClaim::where('customer_id', $customer->id)->firstOrFail();
        $invoice = Invoice::findOrFail($claim->service_invoice_id);

        $this->assertSame(800.0, (float) $claim->service_charge);
        $this->assertSame(800.0, (float) $invoice->due_amount);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'product_name' => 'Repair/service charge - '.$claim->claim_no,
            'total' => 800,
            'service_note' => 'Router repair service charge',
        ]);

        $this->actingAs($user)
            ->get(route('warranty-claims.show', $claim))
            ->assertOk()
            ->assertSee('Service Charge')
            ->assertSee('Invoice '.$invoice->invoice_no)
            ->assertSee('Accounting Added');
    }
}
