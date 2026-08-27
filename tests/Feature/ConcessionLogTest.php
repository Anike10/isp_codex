<?php

namespace Tests\Feature;

use App\Models\ConcessionLog;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
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
