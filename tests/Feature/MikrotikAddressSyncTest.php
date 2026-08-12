<?php

namespace Tests\Feature;

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

    public function test_active_session_saves_ip_and_mac_and_pins_dynamic_secret_address(): void
    {
        $router = $this->router();
        $package = $this->package('Home 20', 'home-20', '20 Mbps');
        $customer = $this->customer($router, 'party-20');
        $this->subscribe($customer, $package);

        $client = Mockery::mock(RouterOsClient::class);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', [
            '.proplist' => '.id,name,address,caller-id,profile,service',
        ])->andReturn([[
            '.id' => '*A1', 'name' => 'party-20', 'address' => '10.20.0.25',
            'caller-id' => 'aa:bb:cc:dd:ee:ff', 'profile' => 'home-20', 'service' => 'pppoe',
        ]]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/print', [
            '?name' => 'party-20', '.proplist' => '.id,remote-address',
        ])->andReturn([['.id' => '*S1', 'remote-address' => '']]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', [
            '.id' => '*S1', 'remote-address' => '10.20.0.25',
        ])->andReturn([]);

        $captured = app(MikrotikCustomerSyncService::class)->captureActiveSessions($client, $router);

        $this->assertSame(1, $captured);
        $customer->refresh();
        $this->assertSame('10.20.0.25', $customer->last_connected_ip);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $customer->last_connected_mac);
        $this->assertSame('10.20.0.25', $customer->learned_ip_address);
        $this->assertSame($package->id, $customer->learned_ip_package_id);
        $this->assertNotNull($customer->last_connected_at);
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
            '?name' => 'party-50', '.proplist' => '.id,profile,disabled,remote-address',
        ])->andReturn([['.id' => '*S50', 'profile' => 'old-profile', 'disabled' => 'false', 'remote-address' => '10.1.1.1']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'home-50', '.proplist' => '.id,remote-address,rate-limit',
        ])->andReturn([['.id' => '*P50', 'rate-limit' => '20M/20M']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/set', [
            '.id' => '*P50', 'rate-limit' => '50M/50M',
        ])->andReturn([]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', Mockery::on(fn (array $payload) =>
            $payload['.id'] === '*S50'
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

    public function test_package_change_clears_dynamic_ip_until_the_new_profile_connects(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $router = $this->router();
        $oldPackage = $this->package('Home 20', 'home-20', '20 Mbps');
        $newPackage = $this->package('Home 30', 'home-30', '30 Mbps');
        $customer = $this->customer($router, 'party-change', [
            'learned_ip_address' => '10.20.0.30',
            'learned_ip_package_id' => $oldPackage->id,
        ]);
        $this->subscribe($customer, $oldPackage);

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
        $this->assertSame($newPackage->id, $customer->activeSubscription()->value('internet_package_id'));
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
            '?name' => 'party-new-package', '.proplist' => '.id,profile,disabled,remote-address',
        ])->andReturn([['.id' => '*SN', 'profile' => 'new-30', 'disabled' => 'false', 'remote-address' => '10.20.0.40']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'new-30', '.proplist' => '.id,remote-address,rate-limit',
        ])->andReturn([['.id' => '*PN', 'rate-limit' => '30M/30M']]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', Mockery::on(fn (array $payload) =>
            $payload['.id'] === '*SN'
            && $payload['profile'] === 'new-30'
            && array_key_exists('remote-address', $payload)
            && $payload['remote-address'] === ''
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
            '?name' => 'party-no-remote', '.proplist' => '.id,profile,disabled,remote-address',
        ])->andReturn([['.id' => '*SNR', 'profile' => 'home-30', 'disabled' => 'false']]);
        $client->shouldReceive('command')->once()->with('/ppp/profile/print', [
            '?name' => 'home-30', '.proplist' => '.id,remote-address,rate-limit',
        ])->andReturn([['.id' => '*PNR', 'rate-limit' => '30M/30M']]);
        $client->shouldReceive('command')->once()->with('/ppp/secret/set', Mockery::on(fn (array $payload) =>
            $payload['.id'] === '*SNR'
            && $payload['profile'] === 'home-30'
            && ! array_key_exists('remote-address', $payload)
        ))->andReturn([]);

        $method = new \ReflectionMethod(MikrotikCustomerSyncService::class, 'syncPppSecret');
        $status = $method->invoke(app(MikrotikCustomerSyncService::class), $client, $customer, $router);

        $this->assertSame('updated', $status);
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
