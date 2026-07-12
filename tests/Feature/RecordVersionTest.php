<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\BkashSmsPayment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\RecordVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_edit_records_old_version_with_editor(): void
    {
        $user = User::factory()->create(['name' => 'Audit User']);
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Audit Party',
            'phone' => '01710000000',
            'connection_id' => 'AUD-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-AUD-001',
            'billing_month' => '2026-07',
            'invoice_type' => 'service',
            'subtotal' => 500,
            'discount' => 0,
            'discount_type' => 'amount',
            'discount_value' => 0,
            'vat' => 0,
            'vat_type' => 'amount',
            'vat_value' => 0,
            'total' => 500,
            'paid_amount' => 0,
            'due_amount' => 500,
            'status' => 'unpaid',
        ]);
        $invoice->items()->create([
            'product_name' => 'Old Service',
            'quantity' => 1,
            'unit_price' => 500,
            'total' => 500,
        ]);

        $this->actingAs($user)->put(route('invoices.update', $invoice), [
            'customer_id' => $customer->id,
            'billing_month' => '2026-07',
            'invoice_type' => 'service',
            'items' => [
                [
                    'product_name' => 'New Service',
                    'quantity' => 1,
                    'unit_price' => 700,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect(route('invoices.show', $invoice));

        $version = RecordVersion::where('versionable_type', Invoice::class)
            ->where('versionable_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(1, RecordVersion::where('versionable_type', Invoice::class)->where('versionable_id', $invoice->id)->count());
        $this->assertSame('Audit User', $version->edited_by_name);
        $this->assertSame(500, (int) $version->old_values['total']);
        $this->assertSame('Old Service', $version->old_values['items'][0]['product_name']);
        $this->assertSame(700, (int) $version->new_values['total']);
        $this->assertContains('items', $version->changed_fields);

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Edit History')
            ->assertSee('View Old Version')
            ->assertDontSee('History Copy')
            ->assertSee('Subtotal')
            ->assertSee('Audit User')
            ->assertSee('Old Service')
            ->assertDontSee('json_encode');
    }

    public function test_party_edit_records_old_version_latest_first(): void
    {
        $user = User::factory()->create(['name' => 'Party Editor']);
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Version Party',
            'phone' => '01720000000',
            'connection_id' => 'VER-001',
            'address' => 'Old Address',
            'status' => 'active',
            'is_customer' => true,
        ]);

        $payload = [
            'name' => 'Version Party',
            'phone' => '01721111111',
            'email' => '',
            'connection_id' => 'VER-001',
            'address' => 'New Address',
            'status' => 'active',
            'is_customer' => '1',
        ];

        $this->actingAs($user)->put(route('customers.update', $customer), $payload)
            ->assertRedirect(route('customers.show', $customer));

        $payload['phone'] = '01722222222';

        $this->actingAs($user)->put(route('customers.update', $customer), $payload)
            ->assertRedirect(route('customers.show', $customer));

        $versions = $customer->versions()->get();

        $this->assertCount(2, $versions);
        $this->assertSame('01721111111', $versions->first()->old_values['phone']);
        $this->assertSame('01720000000', $versions->last()->old_values['phone']);

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Edit History')
            ->assertSee('View Old Version')
            ->assertDontSee('History Copy')
            ->assertSee('01721111111');
    }

    public function test_simple_model_update_records_payment_old_version(): void
    {
        $user = User::factory()->create(['name' => 'Payment Editor']);
        $customer = Customer::create([
            'name' => 'Payment Party',
            'phone' => '01730000000',
            'connection_id' => 'PAY-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-PAY-001',
            'billing_month' => '2026-07',
            'invoice_type' => 'service',
            'subtotal' => 1000,
            'discount' => 0,
            'vat' => 0,
            'total' => 1000,
            'paid_amount' => 500,
            'due_amount' => 500,
            'status' => 'partial',
        ]);
        $payment = Payment::create([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-12',
            'note' => 'Old note',
        ]);

        $this->actingAs($user);
        $payment->update(['note' => 'Updated note']);

        $version = RecordVersion::where('versionable_type', Payment::class)
            ->where('versionable_id', $payment->id)
            ->firstOrFail();

        $this->assertSame('Payment Editor', $version->edited_by_name);
        $this->assertSame('Old note', $version->old_values['note']);
        $this->assertSame('Updated note', $version->new_values['note']);
        $this->assertSame(['note'], $version->changed_fields);
    }

    public function test_sms_processing_status_updates_do_not_create_record_versions(): void
    {
        $smsPayment = BkashSmsPayment::create([
            'sms_sender' => 'bKash',
            'raw_sms' => 'You have received Tk 500.00 TrxID ABC123',
            'trx_id' => 'ABC123',
            'amount' => 500,
            'status' => 'pending',
            'message' => 'Pending review.',
        ]);

        $smsPayment->update([
            'status' => 'processed',
            'message' => 'Payment recorded successfully.',
        ]);

        $this->assertDatabaseMissing('record_versions', [
            'versionable_type' => BkashSmsPayment::class,
            'versionable_id' => $smsPayment->id,
        ]);
    }

    public function test_direct_invoice_model_update_is_also_recorded(): void
    {
        $user = User::factory()->create(['name' => 'Direct Editor']);
        $customer = Customer::create([
            'name' => 'Direct Party',
            'phone' => '01740000000',
            'connection_id' => 'DIR-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-DIR-001',
            'billing_month' => '2026-07',
            'invoice_type' => 'service',
            'subtotal' => 1000,
            'discount' => 0,
            'vat' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($user);
        $invoice->update(['private_note' => 'Direct note']);

        $version = RecordVersion::where('versionable_type', Invoice::class)
            ->where('versionable_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame('Direct Editor', $version->edited_by_name);
        $this->assertNull($version->old_values['private_note']);
        $this->assertSame('Direct note', $version->new_values['private_note']);
        $this->assertSame(['private_note'], $version->changed_fields);
    }

    public function test_quotation_edit_records_full_old_item_snapshot(): void
    {
        $user = User::factory()->create(['name' => 'Quotation Editor']);
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Quotation Party',
            'phone' => '01750000000',
            'connection_id' => 'QT-PARTY',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $quotation = Quotation::create([
            'customer_id' => $customer->id,
            'quotation_no' => 'QT-TEST-001',
            'quotation_date' => '2026-07-12',
            'valid_until' => '2026-07-20',
            'billing_month' => '2026-07',
            'invoice_type' => 'product',
            'subtotal' => 300,
            'discount' => 0,
            'discount_type' => 'amount',
            'discount_value' => 0,
            'vat' => 0,
            'vat_type' => 'amount',
            'vat_value' => 0,
            'total' => 300,
            'status' => 'draft',
        ]);
        $quotation->items()->create([
            'product_name' => 'Old Quoted Item',
            'quantity' => 1,
            'unit_price' => 300,
            'total' => 300,
        ]);

        $this->actingAs($user)->put(route('quotations.update', $quotation), [
            'customer_id' => $customer->id,
            'quotation_date' => '2026-07-12',
            'valid_until' => '2026-07-20',
            'billing_month' => '2026-07',
            'invoice_type' => 'product',
            'items' => [
                [
                    'product_name' => 'New Quoted Item',
                    'quantity' => 1,
                    'unit_price' => 450,
                ],
            ],
            'discount_type' => 'amount',
            'discount' => 0,
            'vat_type' => 'amount',
            'vat' => 0,
        ])->assertRedirect(route('quotations.show', $quotation));

        $version = RecordVersion::where('versionable_type', Quotation::class)
            ->where('versionable_id', $quotation->id)
            ->firstOrFail();

        $this->assertSame('Quotation Editor', $version->edited_by_name);
        $this->assertSame('Old Quoted Item', $version->old_values['items'][0]['product_name']);
        $this->assertSame(450, (int) $version->new_values['total']);

        $this->actingAs($user)->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Edit History')
            ->assertSee('View Old Version')
            ->assertDontSee('History Copy')
            ->assertSee('QT-TEST-001')
            ->assertSee('Quotation')
            ->assertSee('Quoted Total')
            ->assertSee('Old Quoted Item');
    }

    public function test_invoice_finalize_and_bulk_finalize_record_full_versions(): void
    {
        $user = User::factory()->create(['name' => 'Finalizer']);
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $user->permissions()->attach(Permission::where('name', 'finalize_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Final Party',
            'phone' => '01760000000',
            'connection_id' => 'FIN-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);

        $first = $this->draftInvoice($customer, 'INV-FIN-001');
        $second = $this->draftInvoice($customer, 'INV-FIN-002');
        $third = $this->draftInvoice($customer, 'INV-FIN-003');

        $this->actingAs($user)->post(route('invoices.finalize', $first))
            ->assertRedirect(route('invoices.show', $first));

        $singleVersion = RecordVersion::where('versionable_type', Invoice::class)
            ->where('versionable_id', $first->id)
            ->firstOrFail();

        $this->assertNull($singleVersion->old_values['finalized_at']);
        $this->assertNotNull($singleVersion->new_values['finalized_at']);
        $this->assertSame('invoice_finalize', $singleVersion->metadata['source']);

        $this->actingAs($user)->post(route('invoices.finalize-selected'), [
            'invoice_ids' => [$second->id, $third->id],
        ])->assertRedirect();

        foreach ([$second, $third] as $invoice) {
            $version = RecordVersion::where('versionable_type', Invoice::class)
                ->where('versionable_id', $invoice->id)
                ->firstOrFail();

            $this->assertNull($version->old_values['finalized_at']);
            $this->assertNotNull($version->new_values['finalized_at']);
            $this->assertSame('invoice_bulk_finalize', $version->metadata['source']);
        }
    }

    private function draftInvoice(Customer $customer, string $invoiceNo): Invoice
    {
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => $invoiceNo,
            'billing_month' => '2026-07',
            'invoice_type' => 'service',
            'subtotal' => 1000,
            'discount' => 0,
            'vat' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
            'status' => 'unpaid',
        ]);

        $invoice->items()->create([
            'product_name' => 'Monthly Service',
            'quantity' => 1,
            'unit_price' => 1000,
            'total' => 1000,
        ]);

        return $invoice;
    }
}
