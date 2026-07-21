<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccountingLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_ledger_is_serialized_by_date_and_time_but_only_displays_the_date(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::whereIn('name', ['manage_customers', 'manage_invoices'])->pluck('id'));
        $customer = Customer::create([
            'name' => 'Ledger Party',
            'phone' => '01700000001',
            'connection_id' => 'LEDGER-001',
            'address' => 'Kushtia',
            'status' => 'active',
        ]);

        $this->travelTo(Carbon::parse('2026-07-20 11:30:45'));
        $lateInvoice = $this->createInvoice($customer, 'INV-LATE', '2026-07');

        $this->travelTo(Carbon::parse('2026-07-20 09:15:10'));
        $earlyInvoice = $this->createInvoice($customer, 'INV-EARLY', '2026-06');

        $response = $this->actingAs($user)->get(route('accounting.ledger', [
            'customer_id' => $customer->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('<th>Date</th>', false)
            ->assertDontSee('Date &amp; Time', false)
            ->assertDontSee('<th>Reference</th>', false)
            ->assertDontSee('INV-EARLY')
            ->assertDontSee('INV-LATE')
            ->assertSeeInOrder([
                'data-href="'.route('invoices.show', $earlyInvoice).'"',
                'data-href="'.route('invoices.show', $lateInvoice).'"',
            ], false);

        $this->assertMatchesRegularExpression(
            '/<td>1<\/td>\s*<td>2026-07-20<\/td>/',
            $response->getContent(),
        );
        $this->assertMatchesRegularExpression(
            '/<td>2<\/td>\s*<td>2026-07-20<\/td>/',
            $response->getContent(),
        );
    }

    public function test_party_ledger_can_paginate_set_a_default_and_print_all_filtered_rows(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::whereIn('name', ['manage_customers', 'manage_invoices'])->pluck('id'));
        $customer = Customer::create([
            'name' => 'Paginated Ledger Party',
            'phone' => '01700000002',
            'connection_id' => 'LEDGER-PAGE-001',
            'address' => 'Kushtia',
            'status' => 'active',
        ]);

        $invoices = [];
        foreach (range(1, 30) as $number) {
            $this->travelTo(Carbon::parse('2026-07-20 08:00:00')->addMinutes($number));
            $invoices[$number] = $this->createInvoice($customer, 'INV-PAGE-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT), '2026-07');
        }

        $firstPage = $this->actingAs($user)->get(route('accounting.ledger', [
            'customer_id' => $customer->id,
            'per_page' => 25,
            'make_per_page_default' => 1,
        ]));

        $firstPage
            ->assertOk()
            ->assertSessionHas('per_page_default.accounting.ledger', 25)
            ->assertSee('Rows per page')
            ->assertSee('Set as Default')
            ->assertSee('Showing rows 1 to 25 | Total rows: 30')
            ->assertSee('data-href="'.route('invoices.show', $invoices[25]).'"', false)
            ->assertDontSee('<th>Party</th>', false)
            ->assertDontSee('<td>Paginated Ledger Party</td>', false)
            ->assertDontSee('<th>Reference</th>', false)
            ->assertDontSee('data-href="'.route('invoices.show', $invoices[26]).'"', false);

        $secondPage = $this->actingAs($user)->get(route('accounting.ledger', [
            'customer_id' => $customer->id,
            'page' => 2,
        ]));

        $secondPage
            ->assertOk()
            ->assertSee('Showing rows 26 to 30 | Total rows: 30')
            ->assertSee('data-href="'.route('invoices.show', $invoices[26]).'"', false);
        $this->assertMatchesRegularExpression(
            '/<td>26<\/td>\s*<td>2026-07-20<\/td>/',
            $secondPage->getContent(),
        );

        $organization = Organization::create([
            'name' => 'Ledger Print Organization',
            'address' => 'Print Road, Dhaka',
            'mobile' => '01800000000',
            'footer_note' => 'Thank you for your business.',
            'is_default' => true,
            'is_active' => true,
        ]);

        $printPage = $this->actingAs($user)->get(route('accounting.ledger.print', [
            'customer_id' => $customer->id,
            'organization_id' => $organization->id,
            'from' => '2026-07-20',
            'to' => '2026-07-20',
        ]));

        $printPage
            ->assertOk()
            ->assertSee('Ledger Print Organization')
            ->assertSee('Print Road, Dhaka')
            ->assertSee('name="organization_id"', false)
            ->assertSee('value="2026-07-20"', false)
            ->assertSee('Period: <strong>2026-07-20 to 2026-07-20</strong>', false)
            ->assertSee('<td class="sl">1</td>', false)
            ->assertSee('<td class="sl">30</td>', false)
            ->assertSee('Print Report')
            ->assertSee('Black &amp; white', false)
            ->assertSee('Color')
            ->assertSee('<body class="bw-print">', false)
            ->assertSee('tbody tr:nth-child(even) td { background:#f1f7f5; }', false)
            ->assertSee('body.bw-print tbody tr:nth-child(even) td { background:#eeeeee; }', false)
            ->assertSee('window.print()', false)
            ->assertSee('.report { width:210mm; min-height:297mm;', false)
            ->assertSee('thead { display:table-header-group; }', false)
            ->assertSee('@page { size:210mm 297mm; margin:9mm; }', false)
            ->assertDontSee('<th class="party">Party</th>', false)
            ->assertDontSee('<td>Paginated Ledger Party</td>', false)
            ->assertDontSee('Reference')
            ->assertDontSee('08:01:00 AM')
            ->assertSee('Thank you for your business.')
            ->assertDontSee('Rows per page');

        $this->actingAs($user)->get(route('accounting.ledger.print', [
            'customer_id' => $customer->id,
            'organization_id' => $organization->id,
            'print_mode' => 'color',
        ]))
            ->assertOk()
            ->assertSee('<body class="color-print">', false)
            ->assertSee('value="color" checked', false);
    }

    private function createInvoice(Customer $customer, string $invoiceNo, string $billingMonth): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => $invoiceNo,
            'billing_month' => $billingMonth,
            'invoice_type' => 'service',
            'subtotal' => 500,
            'discount' => 0,
            'vat' => 0,
            'total' => 500,
            'paid_amount' => 0,
            'due_amount' => 500,
            'status' => 'unpaid',
            'due_date' => '2026-07-31',
        ]);
    }
}
