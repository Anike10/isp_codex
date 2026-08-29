<?php

namespace Tests\Feature;

use App\Models\BkashSmsPayment;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\MikrotikRouter;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\RecordVersion;
use App\Models\Role;
use App\Models\Subscription;
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
            'payment_note' => 'Old payment guidance.',
            'public_note' => 'Old public note.',
            'show_public_note' => true,
            'private_note' => 'Confidential old note.',
        ]);
        $invoice->items()->create([
            'product_name' => 'Old Service',
            'quantity' => 1,
            'unit_price' => 500,
            'total' => 500,
        ]);

        $payload = [
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
        ];

        $this->actingAs($user)->put(route('invoices.update', $invoice), $payload)
            ->assertRedirect(route('invoices.show', $invoice));

        $version = RecordVersion::where('versionable_type', Invoice::class)
            ->where('versionable_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(1, RecordVersion::where('versionable_type', Invoice::class)->where('versionable_id', $invoice->id)->count());
        $this->assertSame('Audit User', $version->edited_by_name);
        $this->assertSame(500, (int) $version->old_values['total']);
        $this->assertSame('Old Service', $version->old_values['items'][0]['product_name']);
        $this->assertSame(700, (int) $version->new_values['total']);
        $this->assertContains('items', $version->changed_fields);

        $this->actingAs($user)->put(route('invoices.update', $invoice), $payload)
            ->assertRedirect(route('invoices.show', $invoice));

        $this->assertSame(1, RecordVersion::where('versionable_type', Invoice::class)->where('versionable_id', $invoice->id)->count());

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Edit History')
            ->assertSee('View Old Version')
            ->assertDontSee('History Copy')
            ->assertSee('Subtotal')
            ->assertSee('Audit User')
            ->assertSee('Old Service')
            ->assertSee('Old payment guidance.')
            ->assertSee('Old public note.')
            ->assertSee('Confidential old note.')
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
        $package = InternetPackage::create([
            'name' => 'History Package 25',
            'speed' => '25 Mbps',
            'mikrotik_profile' => 'history25',
            'monthly_price' => 1200,
            'status' => 'active',
        ]);
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => '2026-07-01',
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Version Party',
            'phone' => '01721111111',
            'email' => '',
            'connection_id' => 'VER-001',
            'address' => 'New Address',
            'status' => 'active',
            'is_customer' => '1',
            'internet_package_id' => $package->id,
            'start_date' => '2026-07-01',
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

        $this->actingAs($user)->put(route('customers.update', $customer), $payload)
            ->assertRedirect(route('customers.show', $customer));

        $this->assertCount(2, $customer->versions()->get());

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Edit History')
            ->assertSee('View Old Version')
            ->assertDontSee('History Copy')
            ->assertSee('01721111111')
            ->assertSee('History Package 25');
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

    public function test_polling_telemetry_updates_do_not_create_record_versions(): void
    {
        $customer = Customer::create([
            'name' => 'Telemetry Party', 'phone' => '01799999999', 'connection_id' => 'TEL-1',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        $router = MikrotikRouter::create([
            'name' => 'Poll Router', 'ip_address' => '10.9.9.9', 'api_port' => 8728,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        // Pure background-poll writes — must not be audited.
        $customer->update(['last_connected_mac' => '00:11:22:33:44:55', 'last_connected_at' => now(), 'last_connected_ip' => '10.0.0.5']);
        $router->update(['last_api_status' => 'online', 'last_checked_at' => now(), 'last_pppoe_sync_at' => now(), 'last_pppoe_sync_summary' => 'ok']);

        $this->assertDatabaseMissing('record_versions', ['versionable_type' => Customer::class, 'versionable_id' => $customer->id]);
        $this->assertDatabaseMissing('record_versions', ['versionable_type' => MikrotikRouter::class, 'versionable_id' => $router->id]);

        // A real edit still records.
        $customer->update(['name' => 'Telemetry Party Renamed']);
        $this->assertDatabaseHas('record_versions', ['versionable_type' => Customer::class, 'versionable_id' => $customer->id]);
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

        $this->actingAs($user)->post(route('invoices.finalize', $first))
            ->assertRedirect(route('invoices.show', $first));

        $this->assertSame(1, RecordVersion::where('versionable_type', Invoice::class)
            ->where('versionable_id', $first->id)
            ->count());

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

    public function test_role_and_user_permission_only_changes_are_recorded(): void
    {
        $manager = User::factory()->create(['name' => 'Security Manager']);
        $manager->permissions()->attach(Permission::where('name', 'manage_users')->firstOrFail());
        $invoicePermission = Permission::where('name', 'manage_invoices')->firstOrFail();
        $packagePermission = Permission::where('name', 'manage_packages')->firstOrFail();
        $role = Role::create([
            'name' => 'audited_role',
            'label' => 'Audited Role',
        ]);
        $role->permissions()->attach($invoicePermission);

        $this->actingAs($manager)->put(route('roles.update', $role), [
            'name' => 'audited_role',
            'label' => 'Audited Role',
            'permissions' => [$packagePermission->id],
        ])->assertRedirect(route('roles.index'));

        $roleVersion = RecordVersion::where('versionable_type', Role::class)
            ->where('versionable_id', $role->id)
            ->firstOrFail();

        $this->assertContains('permissions', $roleVersion->changed_fields);
        $this->assertSame('manage_invoices', $roleVersion->old_values['permissions'][0]['name']);
        $this->assertSame('manage_packages', $roleVersion->new_values['permissions'][0]['name']);
        $this->assertSame('Security Manager', $roleVersion->edited_by_name);

        $target = User::factory()->create([
            'name' => 'Permission Target',
            'email' => 'permission-target@example.test',
        ]);
        $target->roles()->attach($role);
        $target->permissions()->attach($invoicePermission);

        $this->actingAs($manager)->put(route('users.update', $target), [
            'name' => 'Permission Target',
            'email' => 'permission-target@example.test',
            'password' => 'new-secure-password',
            'roles' => [],
            'permissions' => [$packagePermission->id],
        ])->assertRedirect(route('users.index'));

        $userVersion = RecordVersion::where('versionable_type', User::class)
            ->where('versionable_id', $target->id)
            ->firstOrFail();

        $this->assertContains('roles', $userVersion->changed_fields);
        $this->assertContains('permissions', $userVersion->changed_fields);
        $this->assertContains('login_credential_changed', $userVersion->changed_fields);
        $this->assertArrayNotHasKey('password', $userVersion->old_values);
        $this->assertArrayNotHasKey('password', $userVersion->new_values);
    }

    public function test_record_history_is_paginated_and_uses_id_for_latest_order(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create([
            'name' => 'History Party',
            'phone' => '01770000000',
            'connection_id' => 'HIST-001',
            'address' => 'Kushtia',
            'status' => 'active',
        ]);
        $invoice = $this->draftInvoice($customer, 'INV-HISTORY-001');

        for ($i = 1; $i <= 12; $i++) {
            RecordVersion::create([
                'versionable_type' => Invoice::class,
                'versionable_id' => $invoice->id,
                'table_name' => 'invoices',
                'action' => 'updated',
                'edited_by' => (string) $user->id,
                'edited_by_type' => 'user',
                'edited_by_name' => $user->name,
                'old_values' => ['note' => $i === 1 ? 'EARLIEST-HISTORY-MARKER' : ($i === 12 ? 'LATEST-HISTORY-MARKER' : 'History '.$i)],
                'new_values' => ['note' => 'Updated '.$i],
                'changed_fields' => ['note'],
            ]);
        }

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('LATEST-HISTORY-MARKER')
            ->assertDontSee('EARLIEST-HISTORY-MARKER');

        $this->actingAs($user)->get(route('invoices.show', [
            'invoice' => $invoice,
            'history_page' => 2,
        ]))
            ->assertOk()
            ->assertSee('EARLIEST-HISTORY-MARKER')
            ->assertDontSee('LATEST-HISTORY-MARKER');
    }

    public function test_stock_quantity_only_updates_do_not_duplicate_stock_audit(): void
    {
        $product = Product::create([
            'name' => 'Audit Stock Product',
            'sku' => 'AUDIT-STOCK-001',
            'purchase_price' => 100,
            'sale_price' => 150,
            'stock_quantity' => 0,
            'low_stock_alert' => 1,
        ]);

        $product->update(['stock_quantity' => 5]);

        $this->assertDatabaseMissing('record_versions', [
            'versionable_type' => Product::class,
            'versionable_id' => $product->id,
        ]);

        $product->update([
            'stock_quantity' => 6,
            'sale_price' => 175,
        ]);

        $version = RecordVersion::where('versionable_type', Product::class)
            ->where('versionable_id', $product->id)
            ->firstOrFail();

        $this->assertSame(['sale_price'], $version->changed_fields);
        $this->assertArrayNotHasKey('stock_quantity', $version->old_values);
        $this->assertArrayNotHasKey('stock_quantity', $version->new_values);
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
