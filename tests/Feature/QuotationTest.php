<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_is_stored_separately_without_affecting_invoice_or_stock_accounts(): void
    {
        [$user, $customer, $product] = $this->fixture();

        $this->actingAs($user)->get(route('quotations.create'))
            ->assertOk()
            ->assertSee('Create Quotation')
            ->assertSee(route('quotations.store'), false);

        $response = $this->actingAs($user)->post(route('quotations.store'), $this->quotationPayload($customer, $product));

        $quotation = Quotation::firstOrFail();
        $response->assertRedirect(route('quotations.show', $quotation));
        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total' => 2400,
        ]);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(5, $product->refresh()->stock_quantity);

        $this->actingAs($user)->get(route('quotations.index'))
            ->assertOk()
            ->assertSee($quotation->quotation_no)
            ->assertSee('Accounting note:');
        $this->actingAs($user)->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Make Invoice');
        $this->actingAs($user)->get(route('quotations.print', $quotation))
            ->assertOk()
            ->assertSee($quotation->quotation_no);
    }

    public function test_make_invoice_converts_once_and_applies_inventory_at_conversion_time(): void
    {
        [$user, $customer, $product] = $this->fixture();
        $this->actingAs($user)->post(route('quotations.store'), $this->quotationPayload($customer, $product));
        $quotation = Quotation::firstOrFail();

        $response = $this->actingAs($user)->post(route('quotations.make-invoice', $quotation));

        $invoice = Invoice::firstOrFail();
        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame($invoice->id, $quotation->refresh()->converted_invoice_id);
        $this->assertSame('converted', $quotation->status);
        $this->assertEquals(2400, $invoice->total);
        $this->assertSame(3, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 2,
            'reference_no' => $invoice->invoice_no,
        ]);

        $this->actingAs($user)->post(route('quotations.make-invoice', $quotation))
            ->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->actingAs($user)->get(route('quotations.edit', $quotation))->assertRedirect(route('quotations.show', $quotation));
    }

    public function test_serial_stock_is_not_reserved_by_quotation_and_is_rechecked_during_conversion(): void
    {
        [$user, $customer, $product] = $this->fixture(true);
        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'ONU-001',
            'status' => 'in_stock',
        ]);
        $payload = $this->quotationPayload($customer, $product);
        $payload['items'][0]['quantity'] = 1;
        $payload['items'][0]['serial_numbers'] = 'ONU-001';

        $this->actingAs($user)->post(route('quotations.store'), $payload)->assertRedirect();
        $quotation = Quotation::firstOrFail();
        $this->assertDatabaseHas('product_serials', ['serial_number' => 'ONU-001', 'status' => 'in_stock']);

        ProductSerial::where('serial_number', 'ONU-001')->update(['status' => 'sold']);
        $this->actingAs($user)->from(route('quotations.show', $quotation))
            ->post(route('quotations.make-invoice', $quotation))
            ->assertRedirect(route('quotations.show', $quotation))
            ->assertSessionHasErrors('quotation');

        $this->assertDatabaseCount('invoices', 0);
        $this->assertNull($quotation->refresh()->converted_invoice_id);
        $this->assertSame(5, $product->refresh()->stock_quantity);
    }

    public function test_serial_tracked_quotation_requires_serial_or_serialless_count_for_every_piece(): void
    {
        [$user, $customer, $product] = $this->fixture(true);
        ProductSerial::create([
            'product_id' => $product->id,
            'serial_number' => 'ONU-001',
            'status' => 'in_stock',
        ]);

        $payload = $this->quotationPayload($customer, $product);
        $payload['items'][0]['quantity'] = 3;
        $payload['items'][0]['serial_numbers'] = 'ONU-001';
        $payload['items'][0]['serialless_quantity'] = 1;

        $this->actingAs($user)
            ->post(route('quotations.store'), $payload)
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('quotations', 0);
        $this->assertSame(5, $product->refresh()->stock_quantity);
    }

    private function fixture(bool $serialTracked = false): array
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Quotation Party',
            'phone' => '01710000001',
            'connection_id' => 'QT-CUSTOMER-1',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'is_vendor' => false,
        ]);
        $product = Product::create([
            'name' => 'Quotation Product',
            'sku' => 'QT-PRODUCT-1',
            'product_type' => 'product',
            'track_inventory' => true,
            'track_serial_numbers' => $serialTracked,
            'purchase_price' => 900,
            'sale_price' => 1200,
            'stock_quantity' => 5,
            'low_stock_alert' => 1,
        ]);

        return [$user, $customer, $product];
    }

    private function quotationPayload(Customer $customer, Product $product): array
    {
        return [
            'customer_id' => $customer->id,
            'quotation_date' => '2026-06-19',
            'valid_until' => '2026-07-04',
            'billing_month' => '2026-06',
            'invoice_type' => 'product',
            'items' => [[
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => 2,
                'unit_price' => 1200,
                'serial_numbers' => '',
                'serialless_quantity' => 0,
            ]],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ];
    }
}
