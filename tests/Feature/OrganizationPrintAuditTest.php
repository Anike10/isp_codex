<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPrintAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_printed_for_selected_organization_and_audited(): void
    {
        $user = User::factory()->create(['name' => 'Print Operator']);
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $organization = Organization::create([
            'name' => 'Second Company', 'address' => 'Dhaka Office', 'mobile' => '01900000000', 'is_active' => true,
            'default_without_signature' => true, 'show_organization_selector' => false,
            'bank_name' => 'Test Bank', 'bank_account_name' => 'Second Company Ltd',
            'bank_account_number' => '123456789', 'bank_branch' => 'Main Branch',
            'bank_routing_number' => '987654', 'show_bank_info_on_invoice' => true,
        ]);
        $customer = Customer::create(['name' => 'Test Party', 'phone' => '01700000000', 'connection_id' => 'PRINT-1', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false]);
        $invoice = Invoice::create(['customer_id' => $customer->id, 'invoice_no' => 'INV-PRINT-1', 'billing_month' => '2026-07', 'invoice_type' => 'service', 'subtotal' => 100, 'discount' => 0, 'vat' => 0, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'unpaid']);

        $this->actingAs($user)->get(route('invoices.invoice', ['invoice' => $invoice, 'organization_id' => $organization->id]))
            ->assertOk()
            ->assertSee('Test Party July 2026')
            ->assertSee(route('invoices.pdf', ['invoice' => $invoice, 'organization_id' => $organization->id]), false)
            ->assertSee('<h1>Second Company</h1>', false)
            ->assertSee('01900000000')
            ->assertSee('class="bw-print no-signature', false)
            ->assertSee('id="noSignatureOption" checked', false)
            ->assertSee('id="printOrganization"', false)
            ->assertSee('id="showBankInformationOption" checked', false)
            ->assertSee('show-bank-information')
            ->assertSee('Test Bank')->assertSee('123456789')->assertSee('987654');

        $pdf = $this->actingAs($user)->get(route('invoices.pdf', ['invoice' => $invoice, 'organization_id' => $organization->id]));
        $pdf->assertOk()->assertDownload('Test Party July 2026.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        // The PDF download is recorded in the print history like a print.
        $this->assertDatabaseHas('print_logs', [
            'organization_id' => $organization->id,
            'printable_type' => Invoice::class,
            'printable_id' => $invoice->id,
            'document_type' => 'invoice_pdf',
            'document_no' => 'INV-PRINT-1',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->postJson(route('print-logs.store'), ['organization_id' => $organization->id, 'document_type' => 'invoice', 'printable_id' => $invoice->id])->assertOk();

        $this->assertDatabaseHas('print_logs', ['organization_id' => $organization->id, 'printable_type' => Invoice::class, 'printable_id' => $invoice->id, 'document_type' => 'invoice', 'document_no' => 'INV-PRINT-1', 'user_id' => $user->id, 'user_name' => 'Print Operator']);
        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Print History')
            ->assertSee('Second Company')
            ->assertSee('Print Operator')
            ->assertSeeInOrder(['Payment Allocations', 'Print History']);
    }

    public function test_invoice_pdf_signature_and_bank_blocks_follow_the_toolbar_toggles(): void
    {
        $organization = Organization::create([
            'name' => 'Toggle Co', 'address' => 'Dhaka', 'mobile' => '01911111111', 'is_active' => true,
            'default_without_signature' => true, 'show_bank_info_on_invoice' => true,
            'bank_name' => 'Toggle Bank', 'bank_account_number' => '55554444', 'bank_branch' => 'Central',
        ]);
        $customer = Customer::create(['name' => 'Toggle Party', 'phone' => '01700000001', 'connection_id' => 'TGL-1', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false]);
        $invoice = Invoice::create(['customer_id' => $customer->id, 'invoice_no' => 'INV-TGL-1', 'billing_month' => '2026-07', 'invoice_type' => 'service', 'subtotal' => 100, 'discount' => 0, 'vat' => 0, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'unpaid']);
        $invoice->load(['customer', 'items']);

        $render = fn (array $overrides) => view('invoices.pdf', array_merge([
            'invoice' => $invoice,
            'paymentNote' => 'Pay soon.',
            'organizations' => collect([$organization]),
            'selectedOrganization' => $organization,
            'withoutSignature' => (bool) $organization->default_without_signature,
            'showBankInformation' => (bool) $organization->show_bank_info_on_invoice,
        ], $overrides))->render();

        // Organisation defaults: no signature block, bank info shown.
        $defaults = $render([]);
        $this->assertStringContainsString('No signature required', $defaults);
        $this->assertStringNotContainsString('Authorized Signature', $defaults);
        $this->assertStringContainsString('55554444', $defaults);

        // Toolbar toggles flipped: signature block returns, bank info hidden.
        $flipped = $render(['withoutSignature' => false, 'showBankInformation' => false]);
        $this->assertStringContainsString('Authorized Signature', $flipped);
        $this->assertStringNotContainsString('No signature required', $flipped);
        $this->assertStringNotContainsString('55554444', $flipped);
    }

    public function test_delivery_challan_pdf_downloads_and_is_audited(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $organization = Organization::create(['name' => 'Challan Co', 'address' => 'Dhaka', 'mobile' => '01944444444', 'is_active' => true]);
        $customer = Customer::create(['name' => 'Challan Party', 'phone' => '01700000004', 'connection_id' => 'CHL-1', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false]);
        $invoice = Invoice::create(['customer_id' => $customer->id, 'invoice_no' => 'INV-CHL-1', 'billing_month' => '2026-07', 'invoice_type' => 'service', 'subtotal' => 100, 'discount' => 0, 'vat' => 0, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'unpaid']);

        $pdf = $this->actingAs($user)->get(route('invoices.delivery-challan.pdf', ['invoice' => $invoice, 'organization_id' => $organization->id]));
        $pdf->assertOk()->assertDownload('Challan Party July 2026 Challan.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $this->assertDatabaseHas('print_logs', [
            'organization_id' => $organization->id,
            'printable_type' => Invoice::class,
            'printable_id' => $invoice->id,
            'document_type' => 'delivery_challan_pdf',
            'document_no' => 'INV-CHL-1',
            'user_id' => $user->id,
        ]);
    }

    public function test_invoices_index_more_menu_has_pdf_download_links(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create(['name' => 'Menu Party', 'phone' => '01700000005', 'connection_id' => 'MNU-1', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false]);
        $invoice = Invoice::create(['customer_id' => $customer->id, 'invoice_no' => 'INV-MNU-1', 'billing_month' => '2026-07', 'invoice_type' => 'service', 'subtotal' => 100, 'discount' => 0, 'vat' => 0, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'unpaid']);

        $this->actingAs($user)->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('Download PDF Invoice')
            ->assertSee('Download PDF Challan')
            ->assertSee(route('invoices.pdf', $invoice), false)
            ->assertSee(route('invoices.delivery-challan.pdf', $invoice), false);
    }

    public function test_invoice_detail_page_has_pdf_download_buttons(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $customer = Customer::create(['name' => 'Detail Party', 'phone' => '01700000006', 'connection_id' => 'DTL-1', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false]);
        $invoice = Invoice::create(['customer_id' => $customer->id, 'invoice_no' => 'INV-DTL-1', 'billing_month' => '2026-07', 'invoice_type' => 'service', 'subtotal' => 100, 'discount' => 0, 'vat' => 0, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'unpaid']);

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Download PDF Invoice')
            ->assertSee('Download PDF Challan')
            ->assertSee(route('invoices.pdf', $invoice), false)
            ->assertSee(route('invoices.delivery-challan.pdf', $invoice), false);
    }

    public function test_invoice_pdf_route_honours_the_without_signature_query_flag(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $organization = Organization::create([
            'name' => 'Flag Co', 'address' => 'Dhaka', 'mobile' => '01922222222', 'is_active' => true,
            'default_without_signature' => true,
        ]);
        $customer = Customer::create(['name' => 'Flag Party', 'phone' => '01700000002', 'connection_id' => 'FLG-1', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false]);
        $invoice = Invoice::create(['customer_id' => $customer->id, 'invoice_no' => 'INV-FLG-1', 'billing_month' => '2026-07', 'invoice_type' => 'service', 'subtotal' => 100, 'discount' => 0, 'vat' => 0, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'unpaid']);

        $pdf = $this->actingAs($user)->get(route('invoices.pdf', [
            'invoice' => $invoice, 'organization_id' => $organization->id, 'without_signature' => '0',
        ]));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_invoice_pdf_download_sweeps_stray_files_from_output_pdf(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $organization = Organization::create(['name' => 'Sweep Co', 'address' => 'Dhaka', 'mobile' => '01933333333', 'is_active' => true]);
        $customer = Customer::create(['name' => 'Sweep Party', 'phone' => '01700000003', 'connection_id' => 'SWP-1', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false]);
        $invoice = Invoice::create(['customer_id' => $customer->id, 'invoice_no' => 'INV-SWP-1', 'billing_month' => '2026-07', 'invoice_type' => 'service', 'subtotal' => 100, 'discount' => 0, 'vat' => 0, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'unpaid']);

        $dir = base_path('output/pdf');
        @mkdir($dir, 0755, true);
        file_put_contents($dir.'/old-invoice.pdf', '%PDF-stale');
        file_put_contents($dir.'/keep.txt', 'not a pdf');

        try {
            $this->actingAs($user)
                ->get(route('invoices.pdf', ['invoice' => $invoice, 'organization_id' => $organization->id]))
                ->assertOk();

            $this->assertFileDoesNotExist($dir.'/old-invoice.pdf');
            $this->assertFileExists($dir.'/keep.txt');
        } finally {
            @unlink($dir.'/old-invoice.pdf');
            @unlink($dir.'/keep.txt');
            @rmdir($dir);
            @rmdir(base_path('output'));
        }
    }

    public function test_only_one_organization_is_default_after_update(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $first = Organization::defaultOrganization();

        $this->actingAs($user)->post(route('organizations.store'), ['name' => 'New Default', 'address' => 'Dhaka', 'mobile' => '01800000000', 'is_default' => 1, 'is_active' => 1])->assertRedirect(route('organizations.index'));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertDatabaseHas('organizations', ['name' => 'New Default', 'is_default' => true, 'is_active' => true, 'show_organization_selector' => true]);
        $this->assertSame(1, Organization::where('is_default', true)->count());
    }
}
