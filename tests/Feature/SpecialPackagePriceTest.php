<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BillingService;
use App\Services\BulkCustomerPaymentService;
use App\Services\MikrotikCustomerSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialPackagePriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_price_prefers_the_custom_price(): void
    {
        [$customer, $subscription] = $this->party(1000);

        $this->assertSame(1000.0, $subscription->effectivePrice());
        $this->assertFalse($subscription->hasCustomPrice());

        $subscription->update(['custom_price' => 725.50]);

        $this->assertSame(725.50, $subscription->fresh()->effectivePrice());
        $this->assertTrue($subscription->fresh()->hasCustomPrice());
        $this->assertSame(725.50, $customer->fresh()->load('activeSubscription.package')->effectiveMonthlyPrice());
    }

    public function test_setting_the_special_price_requires_the_permission(): void
    {
        [$customer] = $this->party(1000);

        $plain = $this->userWith('manage_customers');
        $this->actingAs($plain)->post(route('customers.special-price', $customer), ['custom_price' => 800])
            ->assertForbidden();
        $this->assertNull($customer->activeSubscription->fresh()->custom_price);

        $this->actingAs($plain)->get(route('customers.index'))->assertOk()->assertDontSee('Special Price');

        $allowed = $this->userWith('manage_customers', 'set_special_package_price');
        $this->actingAs($allowed)->post(route('customers.special-price', $customer), ['custom_price' => 800])
            ->assertRedirect();
        $this->assertSame(800.0, (float) $customer->activeSubscription->fresh()->custom_price);
        $this->assertStringContainsString('Special package price set to BDT 800.00', $customer->fresh()->notes);

        $this->actingAs($allowed)->get(route('customers.index'))->assertOk()->assertSee('Special Price');
    }

    public function test_a_blank_value_clears_the_special_price(): void
    {
        [$customer, $subscription] = $this->party(1000);
        $subscription->update(['custom_price' => 800]);

        $this->actingAs($this->userWith('manage_customers', 'set_special_package_price'))
            ->post(route('customers.special-price', $customer), ['custom_price' => ''])
            ->assertRedirect();

        $this->assertNull($subscription->fresh()->custom_price);
        $this->assertStringContainsString('Special package price cleared', $customer->fresh()->notes);
    }

    public function test_the_current_month_service_bill_uses_the_special_price(): void
    {
        [$customer, $subscription] = $this->party(1000);
        $subscription->update(['custom_price' => 640]);

        app(BillingService::class)->generateCurrentServiceBillForCustomer($customer->fresh(), '2026-07');

        $invoice = $customer->invoices()->where('billing_month', '2026-07')->firstOrFail();
        $this->assertSame(640.0, (float) $invoice->subtotal);
        $this->assertSame(640.0, (float) $invoice->gross_total);
        $this->assertSame(640.0, (float) $invoice->total);
        $this->assertSame(640.0, (float) $invoice->due_amount);
    }

    public function test_a_renewal_bill_uses_the_special_price(): void
    {
        [$customer, $subscription] = $this->party(1200);
        $subscription->update(['custom_price' => 900]);

        $invoice = app(BillingService::class)->generateNextRenewalServiceBillForCustomer($customer->fresh(), '2026-07-05');

        $this->assertNotNull($invoice);
        $this->assertSame(900.0, (float) $invoice->total);
        $this->assertSame(900.0, (float) $invoice->due_amount);
        $this->assertSame(0.0, (float) $invoice->reseller_commission_amount);
    }

    public function test_a_bulk_payment_charges_the_special_price(): void
    {
        [$customer, $subscription] = $this->party(1000);
        $subscription->update(['custom_price' => 555]);

        $result = app(BulkCustomerPaymentService::class)->record(
            [$customer->id],
            ['duration' => 'month_1', 'payment_date' => '2026-06-01', 'payment_method' => 'cash'],
            null,
            'BULKTESTTOKEN01',
        );

        $this->assertSame(555.0, $result['total']);
        $this->assertDatabaseHas('payments', ['customer_id' => $customer->id, 'amount' => 555]);
        $this->assertDatabaseHas('invoices', ['customer_id' => $customer->id, 'total' => 555]);
    }

    public function test_concession_value_follows_the_special_price(): void
    {
        [$customer, $subscription] = $this->party(3000);
        $subscription->update(['custom_price' => 1500]);
        $customer->update(['service_valid_until' => now()->subDay()->toDateString()]);

        $actor = $this->userWith('manage_customers', 'grant_grace_period');
        $this->actingAs($actor)
            ->post(route('customers.grace-period', $customer), ['grace_days' => 10])
            ->assertRedirect();

        $log = $customer->concessionLogs()->where('action_type', 'grace_period')->firstOrFail();
        $this->assertSame(1500.0, (float) $log->package_monthly_price);
        // 10 free days valued at the special monthly rate, not the 3000 list rate.
        $monthDays = now()->diffInDays(now()->copy()->addMonthNoOverflow());
        $this->assertSame(round(10 * (1500 / $monthDays), 2), (float) $log->estimated_value);
    }

    public function test_changing_the_package_drops_the_special_price(): void
    {
        [$customer, $subscription] = $this->party(1000);
        $subscription->update(['custom_price' => 700]);
        $other = InternetPackage::create(['name' => 'Other', 'speed' => '20', 'monthly_price' => 1500, 'status' => 'active']);

        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('sync')->andReturn('updated');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $this->actingAs($this->userWith('manage_customers'))
            ->patch(route('customers.inline-update', $customer), ['field' => 'package', 'value' => $other->id])
            ->assertOk();

        $subscription->refresh();
        $this->assertSame((int) $other->id, (int) $subscription->internet_package_id);
        $this->assertNull($subscription->custom_price);
    }

    /** @return array{0: Customer, 1: Subscription} */
    private function party(float $monthlyPrice): array
    {
        $package = InternetPackage::create([
            'name' => 'Pkg '.$monthlyPrice, 'speed' => '10 Mbps',
            'monthly_price' => $monthlyPrice, 'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Priced Party', 'phone' => '017'.random_int(10000000, 99999999),
            'connection_id' => 'PRC-'.random_int(1000, 9999), 'address' => 'Kushtia',
            'status' => 'active', 'is_customer' => true,
        ]);
        $subscription = Subscription::create([
            'customer_id' => $customer->id, 'internet_package_id' => $package->id,
            'start_date' => now()->subMonth()->toDateString(), 'status' => 'active',
        ]);

        return [$customer->fresh(), $subscription];
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $name) {
            $user->permissions()->attach(Permission::where('name', $name)->firstOrFail());
        }

        return $user->fresh();
    }
}
