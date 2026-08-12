<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvoiceAuditDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_page_shows_who_created_it_and_when(): void
    {
        $user = User::factory()->create(['name' => 'Invoice Creator']);
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $this->actingAs($user);
        $this->travelTo(Carbon::parse('2026-07-21 14:35:42'));

        $customer = Customer::create([
            'name' => 'Audit Customer',
            'phone' => '01710000001',
            'connection_id' => 'AUDIT-INV-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-CREATOR-001',
            'billing_month' => '2026-07',
            'invoice_type' => 'service',
            'subtotal' => 500,
            'discount' => 0,
            'vat' => 0,
            'total' => 500,
            'paid_amount' => 0,
            'due_amount' => 500,
            'status' => 'unpaid',
        ]);

        $this->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Created By:')
            ->assertSee('Invoice Creator')
            ->assertSee('Created At:')
            ->assertSee('21/07/2026, 02:35:42 PM');
    }
}
