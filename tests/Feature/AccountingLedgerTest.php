<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccountingLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_ledger_is_serialized_by_date_and_time(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Ledger Party',
            'phone' => '01700000001',
            'connection_id' => 'LEDGER-001',
            'address' => 'Kushtia',
            'status' => 'active',
        ]);

        $this->travelTo(Carbon::parse('2026-07-20 11:30:45'));
        $this->createInvoice($customer, 'INV-LATE', '2026-07');

        $this->travelTo(Carbon::parse('2026-07-20 09:15:10'));
        $this->createInvoice($customer, 'INV-EARLY', '2026-06');

        $response = $this->actingAs($user)->get(route('accounting.ledger', [
            'customer_id' => $customer->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('Date &amp; Time', false)
            ->assertSeeInOrder([
                '2026-07-20 09:15:10 AM',
                'INV-EARLY',
                '2026-07-20 11:30:45 AM',
                'INV-LATE',
            ]);

        $this->assertMatchesRegularExpression(
            '/<td>1<\/td>\s*<td>2026-07-20 09:15:10 AM<\/td>/',
            $response->getContent(),
        );
        $this->assertMatchesRegularExpression(
            '/<td>2<\/td>\s*<td>2026-07-20 11:30:45 AM<\/td>/',
            $response->getContent(),
        );
    }

    private function createInvoice(Customer $customer, string $invoiceNo, string $billingMonth): void
    {
        Invoice::create([
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
