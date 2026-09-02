<?php

namespace Tests\Feature;

use App\Models\AppIpPool;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
use App\Services\RouterOsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MikrotikAddressSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_session_saves_last_ip_and_mac_without_pinning_dynamic_secret_address(): void
    {
        $router = $this->router();
        $package = $this->package('Home 20', 'home-20', '20 Mbps');
        AppIpPool::create([
            'mikrotik_router_id' => $router->id,
            'name' => 'home-20-pool',
            'ranges' => '10.20.0.0/24',
            'status' => 'active',
        ]);
        $package->update(['default_ip_pool' => 'home-20-pool']);
        $customer = $this->customer($router, 'party-20');
        $this->subscribe($customer, $package);

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '.proplist' => '.id,name,address,caller-id,profile,service',
        ])->andReturn([[
            '.id' => '*A1', 'name' => 'party-20', 'address' => '10.20.0.25',
            'caller-id' => 'aa:bb:cc:dd:ee:ff', 'profile' => 'home-20', 'service' => 'pppoe',
        ]]);
        $captured = app(MikrotikCustomerSyncService::class)->captureActiveSessions($client, $router);

        $this->assertSame(1, $captured);
        $customer->refresh();
        $this->assertSame('10.20.0.25', $customer->last_connected_ip);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $customer->last_connected_mac);
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->learned_ip_package_id);
        $this->assertNotNull($customer->last_connected_at);
    }

    public function test_session_ip_outside_the_package_pool_is_not_learned_or_pinned(): void
    {
        $router = $this->router();
        AppIpPool::create([
            'mikrotik_router_id' => $router->id,
            'name' => 'kpi-all',
            'ranges' => '10.99.8.0/24',
            'status' => 'active',
        ]);
        $package = $this->package('KPI 30', 'kpi-30', '30 Mbps');
        $package->update(['default_ip_pool' => 'kpi-all']);
        $customer = $this->customer($router, 'party-wrong-pool');
        $this->subscribe($customer, $package);

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '.proplist' => '.id,name,address,caller-id,profile,service',
        ])->andReturn([[
            '.id' => '*WRONG',
            'name' => 'party-wrong-pool',
            'address' => '10.99.1.25',
            'caller-id' => 'AA:BB:CC:DD:EE:FF',
            'profile' => 'kpi-30',
            'service' => 'pppoe',
        ]]);

        $captured = app(MikrotikCustomerSyncService::class)->captureActiveSessions($client, $router);

        $this->assertSame(1, $captured);
        $customer->refresh();
        $this->assertSame('10.99.1.25', $customer->last_connected_ip);
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->learned_ip_package_id);
    }

    public function test_invalid_learned_ip_is_cleared_and_the_wrong_session_is_disconnected(): void
    {
        $router = $this->router();
        AppIpPool::create([
            'mikrotik_router_id' => $router->id,
            'name' => 'kpi-all',
            'ranges' => '10.99.8.2-10.99.8.254',
            'status' => 'active',
        ]);
        $package = $this->package('KPI 50', 'kpi-50', '50 Mbps');
        $package->update(['default_ip_pool' => 'kpi-all']);
        $customer = $this->customer($router, 'party-stale-pool');
        $this->subscribe($customer, $package);
        $customer->update([
            'learned_ip_address' => '10.99.1.40',
            'learned_ip_package_id' => $package->id,
            'last_connected_ip' => '10.99.1.40',
            'last_connected_at' => now(),
        ]);
        $customer->load('activeSubscription.package');

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '?name' => 'party-stale-pool',
            '.proplist' => '.id,name,password,profile,service,comment,disabled,remote-address',
        ])->andReturn([[
            '.id' => '*STALE',
            'name' => 'party-stale-pool',
            'password' => '4321',
            'profile' => 'kpi-50',
            'service' => 'pppoe',
            'comment' => 'Test Party',
            'disabled' => 'false',
            'remote-address' => '10.99.1.40',
        ]]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'kpi-50',
            '.proplist' => '.id,name,remote-address,rate-limit,use-ipv6',
        ])->andReturn([[
            '.id' => '*KPI',
            'name' => 'kpi-50',
            'remote-address' => 'kpi-all',
            'rate-limit' => '50M/50M',
            'use-ipv6' => 'no',
        ]]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', Mockery::on(fn (array $payload) => $payload['.id'] === '*STALE' && $payload['remote-address'] === '0.0.0.0'
        ))->andReturn([]);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '?name' => 'party-stale-pool',
            '.proplist' => '.id',
        ])->andReturn([['.id' => '*ACTIVE']]);
        $client->shouldReceive('command')->once()->with('/ppp/active/remove', [
            '.id' => '*ACTIVE',
        ])->andReturn([]);

        $method = new \ReflectionMethod(MikrotikCustomerSyncService::class, 'syncPppSecret');
        $status = $method->invoke(app(MikrotikCustomerSyncService::class), $client, $customer, $router);

        $this->assertSame('updated', $status);
        $customer->refresh();
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->learned_ip_package_id);
    }

    public function test_fixed_ip_and_package_speed_are_always_applied_to_secret_and_profile(): void
    {
        $router = $this->router();
        $package = $this->package('Home 50', 'home-50', '50 Mbps');
        $customer = $this->customer($router, 'party-50', [
            'use_fixed_ip' => true,
            'fixed_ip_address' => '10.50.0.10',
        ]);
        $this->subscribe($customer, $package);
        $customer->load('activeSubscription.package');

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '?name' => 'party-50', '.proplist' => '.id,name,password,profile,service,comment,disabled,remote-address',
        ])->andReturn([['.id' => '*S50', 'name' => 'party-50', 'profile' => 'old-profile', 'disabled' => 'false', 'remote-address' => '10.1.1.1']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'home-50', '.proplist' => '.id,name,remote-address,rate-limit,use-ipv6',
        ])->andReturn([['.id' => '*P50', 'name' => 'home-50', 'rate-limit' => '20M/20M', 'use-ipv6' => 'yes']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/set', [
            '.id' => '*P50', 'rate-limit' => '50M/50M', 'use-ipv6' => 'no',
        ])->andReturn([]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', Mockery::on(fn (array $payload) => $payload['.id'] === '*S50'
            && $payload['profile'] === 'home-50'
            && $payload['remote-address'] === '10.50.0.10'
        ))->andReturn([]);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '?name' => 'party-50', '.proplist' => '.id',
        ])->andReturn([]);

        $method = new \ReflectionMethod(MikrotikCustomerSyncService::class, 'syncPppSecret');
        $status = $method->invoke(app(MikrotikCustomerSyncService::class), $client, $customer, $router);

        $this->assertSame('updated', $status);
    }

    public function test_new_ppp_profile_is_created_with_ipv6_disabled(): void
    {
        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'new-ipv4-only',
            '.proplist' => '.id,name,remote-address,rate-limit,use-ipv6',
        ])->andReturn([]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/add', [
            'name' => 'new-ipv4-only',
            'use-ipv6' => 'no',
            'remote-address' => 'customer-pool',
            'rate-limit' => '50M/50M',
        ])->andReturn([]);

        app(MikrotikCustomerSyncService::class)->ensurePppProfile(
            $client,
            'new-ipv4-only',
            'customer-pool',
            '50M/50M'
        );
    }

    public function test_special_customer_is_reconnected_to_the_service_profile_even_when_inactive(): void
    {
        $router = $this->router();
        $package = $this->package('Home 30', 'home-30', '30 Mbps');
        $customer = $this->customer($router, 'party-special', [
            'status' => 'inactive',
            'never_suspend' => true,
            'use_fixed_ip' => true,
            'fixed_ip_address' => '10.30.0.9',
        ]);
        // Subscription left inactive on purpose: a suspended special customer.
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => now()->toDateString(),
            'status' => 'inactive',
        ]);

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '?name' => 'party-special', '.proplist' => '.id,name,password,profile,service,comment,disabled,remote-address',
        ])->andReturn([['.id' => '*SS', 'name' => 'party-special', 'profile' => 'inactive', 'disabled' => 'false', 'remote-address' => '0.0.0.0']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'home-30', '.proplist' => '.id,name,remote-address,rate-limit,use-ipv6',
        ])->andReturn([['.id' => '*P30', 'name' => 'home-30', 'rate-limit' => '30M/30M', 'use-ipv6' => 'no']]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', Mockery::on(fn (array $payload) => $payload['.id'] === '*SS'
            && $payload['profile'] === 'home-30'
            && $payload['remote-address'] === '10.30.0.9'
        ))->andReturn([]);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '?name' => 'party-special', '.proplist' => '.id',
        ])->andReturn([]);

        $method = new \ReflectionMethod(MikrotikCustomerSyncService::class, 'syncPppSecret');
        $status = $method->invoke(app(MikrotikCustomerSyncService::class), $client, $customer->fresh(), $router);

        $this->assertSame('updated', $status, 'Special customer should land on the service profile, not moved_inactive.');
    }

    public function test_marking_an_inactive_customer_special_reactivates_the_line(): void
    {
        $user = User::factory()->create();
        foreach (['manage_customers', 'mark_special_customer'] as $name) {
            $user->permissions()->attach(Permission::where('name', $name)->firstOrFail());
        }

        $router = $this->router();
        $package = $this->package('Home 30', 'home-30', '30 Mbps');
        $customer = $this->customer($router, 'party-reactivate', ['status' => 'inactive']);
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => now()->toDateString(),
            'status' => 'inactive',
        ]);

        $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'connection_id' => $customer->connection_id,
            'address' => $customer->address,
            'status' => 'inactive',
            'never_suspend' => '1',
            'mikrotik_router_ids' => [$router->id],
            'internet_package_id' => $package->id,
        ])->assertRedirect();

        $customer->refresh();
        $this->assertTrue((bool) $customer->never_suspend);
        $this->assertSame('active', $customer->status);
        $this->assertTrue($customer->subscriptions()->where('status', 'active')->exists());
    }

    public function test_package_change_clears_dynamic_ip_until_the_new_profile_connects(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $router = $this->router();
        $oldPackage = $this->package('Home 20', 'home-20', '20 Mbps');
        $newPackage = $this->package('Home 30', 'home-30', '30 Mbps');
        $customer = $this->customer($router, 'party-change');
        $this->subscribe($customer, $oldPackage);
        $customer->update([
            'learned_ip_address' => '10.20.0.30',
            'learned_ip_package_id' => $oldPackage->id,
            'last_connected_ip' => '10.20.0.30',
            'last_connected_at' => now(),
        ]);

        $sync = $this->mock(MikrotikCustomerSyncService::class);
        $sync->shouldReceive('sync')->once()->andReturn('updated');

        $this->actingAs($user)->put(route('customers.update', $customer, false), [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'connection_id' => $customer->connection_id,
            'address' => $customer->address,
            'status' => 'active',
            'is_customer' => '1',
            'mikrotik_router_id' => $router->id,
            'internet_package_id' => $newPackage->id,
            'start_date' => now()->toDateString(),
        ])->assertRedirect(route('customers.show', $customer, false));

        $customer->refresh();
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->learned_ip_package_id);
        $this->assertNull($customer->last_connected_ip);
        $this->assertNull($customer->last_connected_at);
        $this->assertSame($newPackage->id, $customer->activeSubscription()->value('internet_package_id'));
    }

    public function test_old_profile_session_is_recorded_only_as_last_connection_history(): void
    {
        $router = $this->router();
        $newPackage = $this->package('New 50', 'new-50', '50 Mbps');
        $customer = $this->customer($router, 'party-old-session');
        $this->subscribe($customer, $newPackage);

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '.proplist' => '.id,name,address,caller-id,profile,service',
        ])->andReturn([[
            '.id' => '*OLD',
            'name' => 'party-old-session',
            'address' => '10.20.0.99',
            'caller-id' => 'AA:BB:CC:DD:EE:FF',
            'profile' => 'old-20',
            'service' => 'pppoe',
        ]]);

        $captured = app(MikrotikCustomerSyncService::class)->captureActiveSessions($client, $router);

        $this->assertSame(1, $captured);
        $customer->refresh();
        $this->assertSame('10.20.0.99', $customer->last_connected_ip);
        $this->assertNotNull($customer->last_connected_at);
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->learned_ip_package_id);
    }

    public function test_dynamic_address_cleanup_releases_secret_and_reconnects_session(): void
    {
        $router = $this->router();
        $customer = $this->customer($router, 'party-auto', [
            'use_fixed_ip' => false,
            'learned_ip_address' => '10.99.3.254',
            'last_connected_ip' => '10.99.3.254',
        ]);

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '.proplist' => '.id,name,remote-address',
        ])->andReturn([[
            '.id' => '*AUTO', 'name' => 'party-auto', 'remote-address' => '10.99.3.254',
        ]]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', [
            '.id' => '*AUTO', 'remote-address' => '0.0.0.0',
        ])->andReturn([]);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '?name' => 'party-auto', '.proplist' => '.id',
        ])->andReturn([['.id' => '*ACTIVE']]);
        $client->shouldReceive('command')->once()->with('/ppp/active/remove', [
            '.id' => '*ACTIVE',
        ])->andReturn([]);

        $summary = app(MikrotikCustomerSyncService::class)->releaseDynamicAddresses($router, $client);

        $this->assertSame(1, $summary['managed']);
        $this->assertSame(1, $summary['released']);
        $this->assertSame(0, $summary['failed']);
        $customer->refresh();
        $this->assertNull($customer->learned_ip_address);
        $this->assertSame('10.99.3.254', $customer->last_connected_ip);
    }

    public function test_dynamic_address_cleanup_accepts_an_already_dynamic_secret_without_an_id(): void
    {
        $router = $this->router();
        $this->customer($router, 'party-already-dynamic', [
            'use_fixed_ip' => false,
        ]);

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '.proplist' => '.id,name,remote-address',
        ])->andReturn([[
            'name' => 'party-already-dynamic',
        ]]);

        $summary = app(MikrotikCustomerSyncService::class)->releaseDynamicAddresses($router, $client);

        $this->assertSame(1, $summary['managed']);
        $this->assertSame(1, $summary['already_dynamic']);
        $this->assertSame(0, $summary['failed']);
    }

    public function test_dynamic_address_cleanup_reports_duplicate_dynamic_secrets_without_failing(): void
    {
        $router = $this->router();
        $this->customer($router, 'party-duplicate-dynamic', [
            'use_fixed_ip' => false,
        ]);

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '.proplist' => '.id,name,remote-address',
        ])->andReturn([
            ['name' => 'party-duplicate-dynamic'],
            ['name' => "party-duplicate-dynamic\n"],
        ]);

        $summary = app(MikrotikCustomerSyncService::class)->releaseDynamicAddresses($router, $client);

        $this->assertSame(1, $summary['managed']);
        $this->assertSame(1, $summary['already_dynamic']);
        $this->assertSame(0, $summary['failed']);
        $this->assertStringContainsString('duplicate PPP secrets', $summary['messages'][0]);
    }

    public function test_dynamic_secret_has_no_remote_address_before_new_package_ip_is_learned(): void
    {
        $router = $this->router();
        $oldPackage = $this->package('Old 20', 'old-20', '20 Mbps');
        $newPackage = $this->package('New 30', 'new-30', '30 Mbps');
        $customer = $this->customer($router, 'party-new-package', [
            'learned_ip_address' => '10.20.0.40',
            'learned_ip_package_id' => $oldPackage->id,
        ]);
        $this->subscribe($customer, $newPackage);
        $customer->load('activeSubscription.package');

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '?name' => 'party-new-package', '.proplist' => '.id,name,password,profile,service,comment,disabled,remote-address',
        ])->andReturn([['.id' => '*SN', 'name' => 'party-new-package', 'profile' => 'new-30', 'disabled' => 'false', 'remote-address' => '10.20.0.40']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'new-30', '.proplist' => '.id,name,remote-address,rate-limit,use-ipv6',
        ])->andReturn([['.id' => '*PN', 'name' => 'new-30', 'rate-limit' => '30M/30M', 'use-ipv6' => 'no']]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', Mockery::on(fn (array $payload) => $payload['.id'] === '*SN'
            && ! array_key_exists('profile', $payload)
            && array_key_exists('remote-address', $payload)
            && $payload['remote-address'] === '0.0.0.0'
        ))->andReturn([]);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '?name' => 'party-new-package', '.proplist' => '.id',
        ])->andReturn([]);

        $method = new \ReflectionMethod(MikrotikCustomerSyncService::class, 'syncPppSecret');
        $status = $method->invoke(app(MikrotikCustomerSyncService::class), $client, $customer, $router);

        $this->assertSame('updated', $status);
    }

    public function test_existing_secret_without_remote_address_does_not_resend_an_empty_value(): void
    {
        $router = $this->router();
        $package = $this->package('Home 30', 'home-30', '30 Mbps');
        $customer = $this->customer($router, 'party-no-remote');
        $this->subscribe($customer, $package);
        $customer->load('activeSubscription.package');

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '?name' => 'party-no-remote', '.proplist' => '.id,name,password,profile,service,comment,disabled,remote-address',
        ])->andReturn([['.id' => '*SNR', 'name' => 'party-no-remote', 'profile' => 'home-30', 'disabled' => 'false']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'home-30', '.proplist' => '.id,name,remote-address,rate-limit,use-ipv6',
        ])->andReturn([['.id' => '*PNR', 'name' => 'home-30', 'rate-limit' => '30M/30M', 'use-ipv6' => 'no']]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', Mockery::on(fn (array $payload) => $payload['.id'] === '*SNR'
            && ! array_key_exists('profile', $payload)
            && ! array_key_exists('remote-address', $payload)
        ))->andReturn([]);

        $method = new \ReflectionMethod(MikrotikCustomerSyncService::class, 'syncPppSecret');
        $status = $method->invoke(app(MikrotikCustomerSyncService::class), $client, $customer, $router);

        $this->assertSame('updated', $status);
    }

    public function test_duplicate_secret_name_is_rejected_before_any_write(): void
    {
        $router = $this->router();
        $package = $this->package('Home 40', 'home-40', '40 Mbps');
        $customer = $this->customer($router, 'party-duplicate');
        $this->subscribe($customer, $package);
        $customer->load('activeSubscription.package');

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '?name' => 'party-duplicate',
            '.proplist' => '.id,name,password,profile,service,comment,disabled,remote-address',
        ])->andReturn([
            ['.id' => '*D1', 'name' => 'party-duplicate', 'service' => 'any', 'profile' => 'home-40'],
            ['.id' => '*D2', 'name' => 'party-duplicate', 'service' => 'pppoe', 'profile' => 'home-40'],
        ]);

        $method = new \ReflectionMethod(MikrotikCustomerSyncService::class, 'syncPppSecret');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate or mismatched PPPoE secrets found for party-duplicate');
        $method->invoke(app(MikrotikCustomerSyncService::class), $client, $customer, $router);
    }

    public function test_router_sync_preflight_rejects_duplicate_names_before_customer_writes(): void
    {
        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '.proplist' => '.id,name',
        ])->andReturn([
            ['.id' => '*A1', 'name' => 'party-a'],
            ['.id' => '*A2', 'name' => 'party-a'],
            ['.id' => '*B1', 'name' => 'party-b'],
        ]);

        $method = new \ReflectionMethod(MikrotikCustomerSyncService::class, 'assertRouterHasUniqueSecretNames');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('RouterOS has 1 duplicate PPPoE username(s) (party-a)');
        $method->invoke(app(MikrotikCustomerSyncService::class), $client);
    }

    private function router(): MikrotikRouter
    {
        return MikrotikRouter::create([
            'name' => 'Core Router', 'ip_address' => '10.0.0.1', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
    }

    private function package(string $name, string $profile, string $speed): InternetPackage
    {
        return InternetPackage::create([
            'name' => $name, 'speed' => $speed, 'mikrotik_profile' => $profile,
            'monthly_price' => 1000, 'status' => 'active',
        ]);
    }

    private function customer(MikrotikRouter $router, string $username, array $extra = []): Customer
    {
        return Customer::create([
            'name' => 'Test Party', 'phone' => '01700000000', 'connection_id' => $username,
            'mikrotik_username' => $username, 'mikrotik_password' => '4321',
            'mikrotik_router_id' => $router->id, 'address' => 'Kushtia',
            'status' => 'active', 'is_customer' => true, ...$extra,
        ]);
    }

    private function subscribe(Customer $customer, InternetPackage $package): void
    {
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }
}
