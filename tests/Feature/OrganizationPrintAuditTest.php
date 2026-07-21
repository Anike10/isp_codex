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
            ->assertSee('<h1>Second Company</h1>', false)
            ->assertSee('01900000000')
            ->assertSee('class="bw-print no-signature', false)
            ->assertSee('id="noSignatureOption" checked', false)
            ->assertSee('id="printOrganization"', false)
            ->assertSee('id="showBankInformationOption" checked', false)
            ->assertSee('show-bank-information')
            ->assertSee('Test Bank')->assertSee('123456789')->assertSee('987654');

        $this->actingAs($user)->postJson(route('print-logs.store'), ['organization_id' => $organization->id, 'document_type' => 'invoice', 'printable_id' => $invoice->id])->assertOk();

        $this->assertDatabaseHas('print_logs', ['organization_id' => $organization->id, 'printable_type' => Invoice::class, 'printable_id' => $invoice->id, 'document_type' => 'invoice', 'document_no' => 'INV-PRINT-1', 'user_id' => $user->id, 'user_name' => 'Print Operator']);
        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Print History')
            ->assertSee('Second Company')
            ->assertSee('Print Operator')
            ->assertSeeInOrder(['Payment Allocations', 'Print History']);
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
