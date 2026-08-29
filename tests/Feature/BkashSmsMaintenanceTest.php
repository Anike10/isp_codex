<?php

namespace Tests\Feature;

use App\Models\BkashSmsPayment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\User;
use App\Services\BkashSmsRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BkashSmsMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $name = 'Nazmul Admin'): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->permissions()->attach(Permission::where('name', 'manage_payments')->firstOrFail());

        return $user;
    }

    private function sms(array $overrides = []): BkashSmsPayment
    {
        return BkashSmsPayment::create(array_merge([
            'sms_sender' => 'bKash',
            'raw_sms' => 'You have received Tk 500.00. TrxID ABC123 at 12/08/2026 10:00 PM',
            'trx_id' => 'ABC123',
            'ledger_trx_id' => 'ABC123',
            'amount' => 500,
            'status' => 'pending',
        ], $overrides));
    }

    public function test_admin_name_is_stored_when_paying_a_customer_from_the_list(): void
    {
        $admin = $this->admin('Nazmul Admin');
        $customer = Customer::create([
            'name' => 'Rahim Ahmed', 'phone' => '01700000000', 'connection_id' => 'KPS-1',
            'address' => 'Kushtia', 'status' => 'active',
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, '2026-08'),
            'billing_month' => '2026-08', 'invoice_type' => 'service',
            'subtotal' => 500, 'discount' => 0, 'vat' => 0, 'total' => 500,
            'paid_amount' => 0, 'due_amount' => 500, 'status' => 'unpaid', 'due_date' => '2026-08-15',
        ]);
        $sms = $this->sms();

        $this->actingAs($admin)
            ->post(route('bkash-sms-payments.approve', $sms), [
                'customer_id' => $customer->id,
                'redirect_to' => 'index',
            ])
            ->assertRedirect(route('bkash-sms-payments.index'));

        $sms->refresh();
        $this->assertSame('processed', $sms->status);
        $this->assertSame('Nazmul Admin', $sms->paid_by_name);
        $this->assertSame($customer->id, $sms->customer_id);
        $this->assertNotNull($sms->payment_id);
        $this->assertSame(0.0, (float) $invoice->refresh()->due_amount);
    }

    public function test_retention_prune_deletes_rows_older_than_the_window(): void
    {
        $admin = $this->admin();

        $old = $this->sms(['trx_id' => 'OLD1', 'ledger_trx_id' => 'OLD1', 'status' => 'processed']);
        $old->forceFill(['created_at' => now()->subDays(40)])->save();
        $new = $this->sms(['trx_id' => 'NEW1', 'ledger_trx_id' => 'NEW1', 'status' => 'processed']);
        $new->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs($admin)->post(route('bkash-sms-payments.maintenance'), [
            'action' => 'save', 'retention_days' => 30,
        ])->assertRedirect();
        $this->assertSame(30, app(BkashSmsRetentionService::class)->retentionDays());
        $this->assertSame(2, BkashSmsPayment::count());

        $this->actingAs($admin)->post(route('bkash-sms-payments.maintenance'), [
            'action' => 'prune_old', 'retention_days' => 30,
        ])->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Deleted 1'));

        $this->assertSame(['NEW1'], BkashSmsPayment::pluck('trx_id')->all());
    }

    public function test_bulk_delete_removes_every_failed_row_only(): void
    {
        $admin = $this->admin();

        $this->sms(['trx_id' => 'F1', 'ledger_trx_id' => 'F1', 'status' => 'failed']);
        $this->sms(['trx_id' => 'F2', 'ledger_trx_id' => 'F2', 'status' => 'failed']);
        $this->sms(['trx_id' => 'P1', 'ledger_trx_id' => 'P1', 'status' => 'processed']);

        $this->actingAs($admin)->post(route('bkash-sms-payments.maintenance'), [
            'action' => 'delete_failed', 'retention_days' => 0,
        ])->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Deleted 2 failed'));

        $this->assertSame(['P1'], BkashSmsPayment::pluck('trx_id')->all());
    }

    public function test_junk_prune_only_removes_unparseable_failed_rows(): void
    {
        $admin = $this->admin();

        // A real payment SMS that failed downstream — keep it.
        $this->sms(['trx_id' => 'REAL', 'ledger_trx_id' => 'REAL', 'amount' => 300, 'status' => 'failed']);
        // Junk: parser found neither a TrxID nor an amount.
        BkashSmsPayment::create([
            'sms_sender' => 'Robi', 'raw_sms' => 'Your OTP is 4821', 'trx_id' => null,
            'amount' => null, 'status' => 'failed', 'message' => 'Could not parse bKash amount or TrxID from SMS.',
        ]);

        $this->actingAs($admin)->post(route('bkash-sms-payments.maintenance'), [
            'action' => 'prune_junk', 'retention_days' => 0,
        ])->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Deleted 1'));

        $this->assertSame(['REAL'], BkashSmsPayment::pluck('trx_id')->filter()->values()->all());
        $this->assertSame(1, BkashSmsPayment::count());
    }

    public function test_junk_auto_delete_setting_drives_the_nightly_command(): void
    {
        $service = app(BkashSmsRetentionService::class);
        $service->setJunkAutoDelete(true);

        BkashSmsPayment::create([
            'sms_sender' => 'Robi', 'raw_sms' => 'Your OTP is 4821',
            'trx_id' => null, 'amount' => null, 'status' => 'failed',
        ]);

        $this->artisan('bkash:prune-sms')->assertSuccessful();

        $this->assertSame(0, BkashSmsPayment::count());
    }

    public function test_list_page_shows_the_inline_pay_control_and_paid_by_column(): void
    {
        $admin = $this->admin('Karim Operator');
        Customer::create([
            'name' => 'Some Party', 'phone' => '01711111111', 'connection_id' => 'KPS-9',
            'address' => 'Kushtia', 'status' => 'active',
        ]);
        $this->sms(['trx_id' => 'PAY1', 'ledger_trx_id' => 'PAY1', 'status' => 'pending', 'entry_by' => 'Counter Redmi Phone']);
        $this->sms(['trx_id' => 'DONE1', 'ledger_trx_id' => 'DONE1', 'status' => 'processed', 'paid_by_name' => 'Karim Operator']);

        $this->actingAs($admin)->get(route('bkash-sms-payments.index'))
            ->assertOk()
            ->assertSee('bkash-party-select', false)
            ->assertSee('Auto-delete junk failed SMS', false)
            ->assertSee('<th>Device</th>', false)
            ->assertSee('Counter Redmi Phone')
            ->assertSee('Karim Operator')
            ->assertSee(route('bkash-sms-payments.approve', 1), false);
    }

    public function test_maintenance_requires_the_manage_payments_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('bkash-sms-payments.maintenance'), ['action' => 'save', 'retention_days' => 5])
            ->assertForbidden();
    }
}
