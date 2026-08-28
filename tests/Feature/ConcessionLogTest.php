<?php

namespace Tests\Feature;

use App\Models\ConcessionLog;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ConcessionLogService;
use App\Services\MikrotikCustomerSyncService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConcessionLogTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_grace_period_route_is_blocked_without_the_new_permission(): void
    {
        $user = $this->userWith(['manage_customers']);
        $customer = $this->customerWithExpiredService(3000);

        $this->actingAs($user)
            ->post(route('customers.grace-period', $customer), ['grace_days' => 10])
            ->assertForbidden();

        $this->assertSame(0, ConcessionLog::count());
    }

    public function test_grace_period_is_logged_with_a_prorated_money_value(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = $this->userWith(['manage_customers', 'grant_grace_period']);
        $customer = $this->customerWithExpiredService(3000);

        $this->actingAs($user)
            ->post(route('customers.grace-period', $customer), ['grace_days' => 10])
            ->assertRedirect();

        $monthDays = Carbon::parse('2026-08-15')->diffInDays(Carbon::parse('2026-08-15')->addMonthNoOverflow());
        $expected = round(10 * (3000 / $monthDays), 2);

        $log = ConcessionLog::firstOrFail();
        $this->assertSame('grace_period', $log->action_type);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(10, $log->free_days);
        $this->assertSame($expected, (float) $log->estimated_value);
    }

    public function test_validity_override_values_only_the_free_days_beyond_paid_validity(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = $this->userWith(['manage_customers', 'override_service_validity']);
        $customer = $this->customerWithExpiredService(3000);
        $customer->update(['service_valid_until' => '2026-08-20']);

        // Push validity 10 days past what was already paid (Aug 20 -> Aug 30).
        $this->actingAs($user)
            ->post(route('customers.service-validity.update', $customer), [
                'service_valid_until' => '2026-08-30',
                'validity_note' => 'Customer promised to pay next week.',
            ])
            ->assertRedirect();

        $monthDays = Carbon::parse('2026-08-20')->diffInDays(Carbon::parse('2026-08-20')->addMonthNoOverflow());
        $expected = round(10 * (3000 / $monthDays), 2);

        $log = ConcessionLog::where('action_type', 'validity_override')->firstOrFail();
        $this->assertSame(10, $log->free_days);
        $this->assertSame($expected, (float) $log->estimated_value);
    }

    public function test_force_active_stays_pending_until_a_payment_settles_its_value(): void
    {
        Carbon::setTestNow('2026-08-10 09:00:00');

        $user = $this->userWith(['manage_customers', 'force_service_status']);
        $customer = $this->customerWithExpiredService(3100);
        $customer->update(['status' => 'inactive']);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, '2026-08'),
            'billing_month' => '2026-08',
            'invoice_type' => 'service',
            'subtotal' => 3100, 'discount' => 0, 'vat' => 0, 'total' => 3100,
            'paid_amount' => 0, 'due_amount' => 3100, 'status' => 'unpaid',
            'due_date' => '2026-08-05',
        ]);

        $this->actingAs($user)
            ->post(route('customers.force-active', $customer), ['active_note' => 'Allowed online for 5 days.'])
            ->assertRedirect();

        $log = ConcessionLog::where('action_type', 'force_active')->firstOrFail();
        $this->assertSame('pending', $log->value_status);
        $this->assertSame(0.0, (float) $log->estimated_value);

        Carbon::setTestNow('2026-08-15 09:00:00');
        $this->paymentService()->recordPayment($invoice->refresh(), [
            'amount' => 3100,
            'payment_method' => 'cash',
            'payment_date' => '2026-08-15',
            'note' => 'Paid after 5 free days.',
        ]);

        $log->refresh();
        $monthDays = Carbon::parse('2026-08-10')->diffInDays(Carbon::parse('2026-08-10')->addMonthNoOverflow());
        $this->assertSame('final', $log->value_status);
        $this->assertSame(5, $log->free_days);
        $this->assertSame(round(5 * (3100 / $monthDays), 2), (float) $log->estimated_value);
    }

    public function test_marking_special_logs_a_running_give_away_that_settles_when_removed(): void
    {
        Carbon::setTestNow('2026-08-10 09:00:00');

        $user = $this->userWith(['manage_customers']);
        $this->actingAs($user);

        $customer = $this->customerWithExpiredService(3000);
        $service = app(ConcessionLogService::class);

        $service->recordSpecialToggle($customer, true, 'Marked special.');

        $log = ConcessionLog::where('action_type', 'mark_special')->firstOrFail();
        $this->assertSame('pending', $log->value_status);
        $this->assertNull($log->closed_at);
        $this->assertTrue($log->isRunning());

        $monthDays = Carbon::parse('2026-08-10')->diffInDays(Carbon::parse('2026-08-10')->addMonthNoOverflow());
        $daily = 3000 / $monthDays;

        // The day the flag was set counts as the first give-away day.
        $this->assertSame(round($daily, 2), $log->displayValue());

        // Five days on it is still running and now worth six days (10th-15th).
        Carbon::setTestNow('2026-08-15 09:00:00');
        $this->assertSame(round(6 * $daily, 2), $log->fresh()->displayValue());

        // Removing the flag settles the same open row at six days.
        $service->recordSpecialToggle($customer, false, 'Removed special.');

        $log->refresh();
        $this->assertSame('final', $log->value_status);
        $this->assertNotNull($log->closed_at);
        $this->assertSame(6, $log->free_days);
        $this->assertSame(round(6 * $daily, 2), (float) $log->estimated_value);
        $this->assertFalse($log->isRunning());
        $this->assertSame(1, ConcessionLog::where('action_type', 'mark_special')->count());
    }

    public function test_paying_after_grace_credits_the_value_back_to_the_grace_admin(): void
    {
        Carbon::setTestNow('2026-08-01 10:00:00');

        $actor = $this->userWith(['manage_customers', 'grant_grace_period']);
        $customer = $this->customerWithExpiredService(3000);

        $this->actingAs($actor)
            ->post(route('customers.grace-period', $customer), ['grace_days' => 6])
            ->assertRedirect();

        $grace = ConcessionLog::where('action_type', 'grace_period')->firstOrFail();
        $graceValue = (float) $grace->estimated_value;
        $this->assertGreaterThan(0, $graceValue);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => Invoice::generateInvoiceNo($customer->id, '2026-08'),
            'billing_month' => '2026-08',
            'invoice_type' => 'service',
            'subtotal' => 3000, 'discount' => 0, 'vat' => 0, 'total' => 3000,
            'paid_amount' => 0, 'due_amount' => 3000, 'status' => 'unpaid',
            'due_date' => '2026-08-01',
        ]);

        Carbon::setTestNow('2026-08-10 09:00:00');
        $this->paymentService()->recordPayment($invoice->refresh(), [
            'amount' => 3000,
            'payment_method' => 'cash',
            'payment_date' => '2026-08-10',
            'note' => 'Recharge after grace.',
        ]);

        $recovery = ConcessionLog::where('action_type', 'grace_recovered')->firstOrFail();
        $this->assertSame($actor->id, $recovery->user_id);
        $this->assertSame(-6, (int) $recovery->free_days);
        $this->assertSame(round(-$graceValue, 2), (float) $recovery->estimated_value);
        $this->assertSame($grace->id, $recovery->meta['recovered_grace_log_id'] ?? null);

        // The grace give-away for this party now nets to zero.
        $net = round((float) ConcessionLog::where('customer_id', $customer->id)->sum('estimated_value'), 2);
        $this->assertSame(0.0, $net);

        // The original grace row is marked so a later payment cannot double-credit.
        $grace->refresh();
        $this->assertSame($recovery->id, $grace->meta['recovered_by_log_id'] ?? null);
        $this->assertSame(1, ConcessionLog::where('action_type', 'grace_recovered')->count());
    }

    public function test_reports_require_the_view_permission_and_show_totals(): void
    {
        $viewer = $this->userWith(['view_concession_reports']);
        $blocked = $this->userWith(['manage_customers']);
        $actor = $this->userWith(['manage_customers', 'grant_grace_period']);

        Carbon::setTestNow('2026-08-15 10:00:00');
        $customer = $this->customerWithExpiredService(3000);
        $this->actingAs($actor)
            ->post(route('customers.grace-period', $customer), ['grace_days' => 10])
            ->assertRedirect();
        Carbon::setTestNow();

        $this->assertDatabaseHas('concession_logs', ['action_type' => 'grace_period', 'user_id' => $actor->id]);

        $this->actingAs($blocked)->get(route('concession-reports.index'))->assertForbidden();

        $this->actingAs($viewer)->get(route('concession-reports.index'))
            ->assertOk()
            ->assertSee('Concession Actions')
            ->assertSee($actor->name);

        $this->actingAs($viewer)->get(route('concession-reports.summary'))
            ->assertOk()
            ->assertSee('Concession Cost by Admin')
            ->assertSee($actor->name);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $name) {
            $user->permissions()->attach(Permission::where('name', $name)->firstOrFail());
        }

        return $user->fresh();
    }

    private function customerWithExpiredService(float $monthlyPrice): Customer
    {
        $package = InternetPackage::create([
            'name' => 'Home '.$monthlyPrice,
            'speed' => '10Mbps',
            'monthly_price' => $monthlyPrice,
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'name' => 'Concession Party',
            'phone' => '01710000000',
            'connection_id' => 'KPS-'.fake()->unique()->numberBetween(2000, 9999),
            'address' => 'Kushtia',
            'status' => 'active',
            'service_valid_until' => now()->subDay()->toDateString(),
        ]);

        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ]);

        return $customer->fresh();
    }

    private function paymentService(): PaymentService
    {
        return new PaymentService(
            $this->createMock(MikrotikCustomerSyncService::class),
            app(\App\Services\ConcessionLogService::class),
        );
    }
}
