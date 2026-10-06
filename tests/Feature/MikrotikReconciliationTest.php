<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use App\Models\MikrotikSyncFailure;
use App\Models\MikrotikSyncIssue;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
use App\Services\MikrotikImportService;
use App\Services\MikrotikReconciliationService;
use App\Services\MikrotikSyncAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MikrotikReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_failed_attempt_is_logged_and_one_open_issue_is_reused(): void
    {
        $router = $this->router();
        $customer = $this->customer($router, 'party-100');
        $audit = app(MikrotikSyncAuditService::class);

        $audit->recordFailure($router, $customer, 'billing_expiry', new RuntimeException('Connection closed'));
        $audit->recordFailure($router, $customer, 'daily_retry', new RuntimeException('Timed out'));

        $this->assertSame(1, MikrotikSyncIssue::whereNull('resolved_at')->count());
        $this->assertSame(2, MikrotikSyncFailure::count());
        $this->assertSame(2, MikrotikSyncIssue::firstOrFail()->attempt_count);
        $this->assertDatabaseHas('mikrotik_sync_failures', [
            'context' => 'billing_expiry',
            'error_message' => 'Connection closed',
        ]);
        $this->assertDatabaseHas('mikrotik_sync_failures', [
            'context' => 'daily_retry',
            'error_message' => 'Timed out',
        ]);
    }

    public function test_nightly_reconciliation_detects_and_resolves_a_wrong_profile(): void
    {
        $router = $this->router();
        $customer = $this->customer($router, 'party-200');
        $package = InternetPackage::create([
            'name' => '20 Mbps',
            'speed' => '20 Mbps',
            'mikrotik_profile' => '20M',
            'monthly_price' => 1000,
            'status' => 'active',
        ]);
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $customerSync = Mockery::mock(MikrotikCustomerSyncService::class);
        $customerSync->shouldReceive('expectedProfile')->twice()->andReturn('20M');
        $customerSync->shouldReceive('syncRouter')->once()->andReturn([
            'created' => 0,
            'updated' => 1,
            'moved_inactive' => 0,
            'skipped' => 0,
            'failed' => 0,
            'active_sessions_captured' => 0,
            'messages' => [],
        ]);

        $import = Mockery::mock(MikrotikImportService::class);
        $import->shouldReceive('liveRecords')
            ->twice()
            ->withArgs(fn (MikrotikRouter $given, string $command): bool => $given->is($router) && $command === '/ppp/secret/print')
            ->andReturn(
                [['.id' => '*1', 'name' => 'party-200', 'profile' => '10M', 'service' => 'pppoe', 'disabled' => 'no']],
                [['.id' => '*1', 'name' => 'party-200', 'profile' => '20M', 'service' => 'pppoe', 'disabled' => 'no']],
            );

        $service = new MikrotikReconciliationService(
            $customerSync,
            $import,
            app(MikrotikSyncAuditService::class),
        );
        $result = $service->reconcileRouter($router);

        $this->assertSame(1, $result['mismatches']);
        $this->assertSame(1, $result['corrected']);
        $this->assertSame(0, $result['failed']);
        $this->assertNotNull(MikrotikSyncIssue::firstOrFail()->resolved_at);
    }

    public function test_mismatch_that_remains_after_sync_is_saved_as_a_failed_attempt(): void
    {
        $router = $this->router();
        $customer = $this->customer($router, 'party-duplicate');

        $customerSync = Mockery::mock(MikrotikCustomerSyncService::class);
        $customerSync->shouldReceive('expectedProfile')->twice()->andReturn('inactive');
        $customerSync->shouldReceive('syncRouter')->once()->andReturn([
            'created' => 0,
            'updated' => 1,
            'moved_inactive' => 0,
            'skipped' => 0,
            'failed' => 0,
            'active_sessions_captured' => 0,
            'messages' => [],
        ]);

        $duplicateRecords = [
            ['.id' => '*1', 'name' => 'party-duplicate', 'profile' => 'inactive', 'service' => 'pppoe', 'disabled' => 'no'],
            ['.id' => '*2', 'name' => 'PARTY-DUPLICATE', 'profile' => '20M', 'service' => 'pppoe', 'disabled' => 'no'],
        ];
        $import = Mockery::mock(MikrotikImportService::class);
        $import->shouldReceive('liveRecords')->twice()->andReturn($duplicateRecords, $duplicateRecords);

        $service = new MikrotikReconciliationService(
            $customerSync,
            $import,
            app(MikrotikSyncAuditService::class),
        );
        $result = $service->reconcileRouter($router);

        $this->assertSame(1, $result['mismatches']);
        $this->assertSame(0, $result['corrected']);
        $this->assertSame(1, $result['failed']);
        $this->assertDatabaseHas('mikrotik_sync_failures', [
            'customer_id' => $customer->id,
            'context' => 'nightly_reconciliation_unresolved',
        ]);
        $this->assertSame(1, MikrotikSyncIssue::whereNull('resolved_at')->firstOrFail()->attempt_count);
    }

    public function test_dashboard_lists_only_issues_unresolved_for_at_least_24_hours(): void
    {
        $user = User::factory()->superAdmin()->create();
        $router = $this->router();
        $customer = $this->customer($router, 'party-old');
        $audit = app(MikrotikSyncAuditService::class);

        $old = $audit->recordFailure($router, $customer, 'daily_retry', 'Still unreachable');
        $old->forceFill(['first_detected_at' => now()->subHours(25)])->save();

        $freshCustomer = $this->customer($router, 'party-new');
        $audit->recordFailure($router, $freshCustomer, 'customer_sync', 'Recent failure');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('MikroTik mismatches unresolved for 24+ hours')
            ->assertSee('party-old')
            ->assertDontSee('party-new');
    }

    private function router(): MikrotikRouter
    {
        return MikrotikRouter::create([
            'name' => 'Core Router',
            'ip_address' => '10.0.0.1',
            'api_port' => 8728,
            'inactive_pppoe_profile' => 'inactive',
            'username' => 'api',
            'password' => 'secret',
            'status' => 'active',
        ]);
    }

    private function customer(MikrotikRouter $router, string $username): Customer
    {
        return Customer::create([
            'name' => 'Test '.$username,
            'phone' => '01700000000',
            'connection_id' => $username,
            'mikrotik_username' => $username,
            'mikrotik_password' => '4321',
            'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
    }
}
