<?php

namespace Tests\Feature;

use App\Models\MikrotikRouter;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use App\Services\MikrotikInactivePortalService;
use App\Services\RouterOsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class InactivePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_can_save_multiple_please_call_numbers(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $organization = Organization::defaultOrganization();

        $this->actingAs($user)->put(route('organizations.update', $organization), [
            'name' => $organization->name,
            'is_default' => 1,
            'is_active' => 1,
            'please_call_numbers' => " 01700 000000 \r\n01800 000000\n01700 000000",
        ])->assertRedirect(route('organizations.index'));

        $organization->refresh();
        $this->assertSame("01700 000000\n01800 000000", str_replace("\r\n", "\n", $organization->please_call_numbers));
        $this->assertSame(['01700 000000', '01800 000000'], $organization->pleaseCallNumbers());
    }

    public function test_organization_edit_page_has_please_call_editor_and_preview_links(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $organization = Organization::defaultOrganization();

        $this->actingAs($user)
            ->get(route('organizations.edit', $organization))
            ->assertOk()
            ->assertSee('Edit Please Call Page')
            ->assertSee('Please Call Page Settings')
            ->assertSee('name="please_call_brand_name"', false)
            ->assertSee('name="please_call_title"', false)
            ->assertSee('name="please_call_message"', false)
            ->assertSee('name="please_call_footer"', false)
            ->assertSee('Preview Please Call Page')
            ->assertSee('href="'.route('service-inactive').'"', false);
    }

    public function test_organization_can_customize_please_call_page_content(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $organization = Organization::defaultOrganization();

        $this->actingAs($user)->put(route('organizations.update', $organization), [
            'name' => $organization->name,
            'is_default' => 1,
            'is_active' => 1,
            'please_call_brand_name' => 'US Support Desk',
            'please_call_title' => 'Connection Paused',
            'please_call_message' => 'Please contact our billing team to restore your service.',
            'please_call_footer' => 'Support is available every day.',
            'please_call_numbers' => '01700 000000',
        ])->assertRedirect(route('organizations.index'));

        $this->get(route('service-inactive'))
            ->assertOk()
            ->assertSee('US Support Desk')
            ->assertSee('Connection Paused')
            ->assertSee('Please contact our billing team to restore your service.')
            ->assertSee('Support is available every day.');
    }

    public function test_please_call_page_is_public_and_shows_every_configured_number(): void
    {
        Organization::defaultOrganization()->update([
            'name' => 'Example ISP',
            'please_call_numbers' => "01700 000000\n+880 1800 000000",
        ]);

        $response = $this->get(route('service-inactive'))
            ->assertOk()
            ->assertSee('Please Call')
            ->assertSee('Example ISP')
            ->assertSee('01700 000000')
            ->assertSee('+880 1800 000000')
            ->assertSee('tel:01700000000', false)
            ->assertSee('tel:+8801800000000', false);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_save_and_apply_button_configures_all_writable_routers(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());
        $organization = Organization::defaultOrganization();
        $service = Mockery::mock(MikrotikInactivePortalService::class);
        $service->shouldReceive('configureAll')->once()->andReturn([
            'configured' => 2,
            'skipped' => 1,
            'failed' => 0,
            'messages' => [],
        ]);
        $this->app->instance(MikrotikInactivePortalService::class, $service);

        $this->actingAs($user)->put(route('organizations.update', $organization), [
            'name' => $organization->name,
            'is_default' => 1,
            'is_active' => 1,
            'please_call_numbers' => '01700 000000',
            'apply_inactive_portal' => 1,
        ])->assertRedirect(route('organizations.index'))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'applied to 2 router(s), skipped 1'));
    }

    public function test_router_configuration_targets_only_the_inactive_profile_and_tagged_rules(): void
    {
        Organization::defaultOrganization()->update(['please_call_numbers' => '01700 000000']);
        URL::forceRootUrl('https://portal.example.test');
        URL::forceScheme('https');

        $router = MikrotikRouter::create([
            'name' => 'Core Router',
            'ip_address' => '10.0.0.1',
            'api_port' => 8728,
            'inactive_pppoe_profile' => 'inactive',
            'username' => 'api',
            'password' => 'secret',
            'status' => 'active',
        ]);
        $client = new PortalFakeRouterOsClient(activeSession: true);

        $result = app(MikrotikInactivePortalService::class)->configureWithClient($client, $router);

        $this->assertSame('inactive', $result['profile']);
        $this->assertSame(MikrotikInactivePortalService::INACTIVE_ADDRESS_LIST, $result['address_list']);
        $this->assertSame('https://portal.example.test/please-call', $result['portal_url']);
        $this->assertSame('10.99.99.1', $result['dns_server']);
        $this->assertSame(1, $result['reconnected']);

        $this->assertCommand($client, '/ppp/profile/set', fn (array $data): bool => ($data['.id'] ?? null) === '*PROFILE'
            && ($data['address-list'] ?? null) === MikrotikInactivePortalService::INACTIVE_ADDRESS_LIST
            && ($data['dns-server'] ?? null) === '10.99.99.1'
        );
        $this->assertCommand($client, '/ip/dns/set', fn (array $data): bool => ($data['allow-remote-requests'] ?? null) === 'yes');
        $this->assertCommand($client, '/ip/proxy/set', fn (array $data): bool => ($data['enabled'] ?? null) === 'yes'
            && ($data['port'] ?? null) === '8080'
            && ($data['src-address'] ?? null) === '10.0.0.1'
            && ($data['max-cache-size'] ?? null) === '1024'
        );
        $this->assertCommand($client, '/ip/firewall/nat/add', fn (array $data): bool => ($data['src-address-list'] ?? null) === MikrotikInactivePortalService::INACTIVE_ADDRESS_LIST
            && ($data['dst-port'] ?? null) === '80'
            && ($data['action'] ?? null) === 'dst-nat'
            && ($data['to-addresses'] ?? null) === '10.0.0.1'
            && ($data['to-ports'] ?? null) === '8080'
        );
        $this->assertCommand($client, '/ip/proxy/access/add', fn (array $data): bool => ($data['action'] ?? null) === 'redirect'
            && ($data['action-data'] ?? null) === 'https://portal.example.test/please-call'
            && ! array_key_exists('redirect-to', $data)
            && ! array_key_exists('place-before', $data)
        );
        $this->assertCommand($client, '/ip/firewall/filter/add', fn (array $data): bool => ($data['chain'] ?? null) === 'input'
            && ($data['src-address-list'] ?? null) === MikrotikInactivePortalService::INACTIVE_ADDRESS_LIST
            && ($data['protocol'] ?? null) === 'udp'
            && ($data['dst-port'] ?? null) === '53'
            && ($data['action'] ?? null) === 'accept'
        );
        $this->assertCommand($client, '/ip/firewall/filter/add', fn (array $data): bool => ($data['chain'] ?? null) === 'input'
            && ! array_key_exists('src-address-list', $data)
            && ($data['protocol'] ?? null) === 'tcp'
            && ($data['dst-port'] ?? null) === '8080'
            && ($data['action'] ?? null) === 'drop'
        );
        $this->assertCommand($client, '/ip/firewall/filter/add', fn (array $data): bool => ($data['chain'] ?? null) === 'input'
            && ($data['src-address-list'] ?? null) === MikrotikInactivePortalService::INACTIVE_ADDRESS_LIST
            && ($data['protocol'] ?? null) === 'tcp'
            && ($data['dst-port'] ?? null) === '53'
            && ($data['action'] ?? null) === 'accept'
        );
        $this->assertCommand($client, '/ip/firewall/filter/add', fn (array $data): bool => ($data['action'] ?? null) === 'reject'
            && str_contains((string) ($data['comment'] ?? ''), 'Please Call')
        );
        $filterComments = collect($client->commands)
            ->where('command', '/ip/firewall/filter/add')
            ->pluck('attributes.comment')
            ->values();
        $portalPosition = $filterComments->search('ISP Codex Please Call allow portal');
        $blockPosition = $filterComments->search('ISP Codex Please Call block other traffic');
        $proxyAllowPosition = $filterComments->search('ISP Codex Please Call allow transparent proxy');
        $proxyDropPosition = $filterComments->search('ISP Codex Please Call block other proxy clients');
        $this->assertIsInt($portalPosition);
        $this->assertIsInt($blockPosition);
        $this->assertIsInt($proxyAllowPosition);
        $this->assertIsInt($proxyDropPosition);
        $this->assertLessThan($blockPosition, $portalPosition);
        $this->assertLessThan($proxyDropPosition, $proxyAllowPosition);
        $this->assertCommand($client, '/ppp/active/remove', fn (array $data): bool => ($data['.id'] ?? null) === '*ACTIVE');
    }

    public function test_existing_unmanaged_router_proxy_is_not_overwritten(): void
    {
        Organization::defaultOrganization()->update(['please_call_numbers' => '01700 000000']);
        URL::forceRootUrl('https://portal.example.test');
        URL::forceScheme('https');

        $router = MikrotikRouter::create([
            'name' => 'Core Router',
            'ip_address' => '10.0.0.2',
            'api_port' => 8728,
            'inactive_pppoe_profile' => 'inactive',
            'username' => 'api',
            'password' => 'secret',
            'status' => 'active',
        ]);
        $client = new PortalFakeRouterOsClient(proxyEnabled: true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Web Proxy already has another configuration');

        app(MikrotikInactivePortalService::class)->configureWithClient($client, $router);
    }

    private function assertCommand(PortalFakeRouterOsClient $client, string $command, callable $matches): void
    {
        $found = collect($client->commands)->contains(
            fn (array $call): bool => $call['command'] === $command && $matches($call['attributes'])
        );

        $this->assertTrue($found, "Expected RouterOS command {$command} was not recorded with the required attributes.");
    }
}

class PortalFakeRouterOsClient extends RouterOsClient
{
    /** @var array<int, array{command: string, attributes: array<string, mixed>}> */
    public array $commands = [];

    public function __construct(
        private readonly bool $proxyEnabled = false,
        private readonly bool $activeSession = false,
    ) {}

    public function command(string $command, array $attributes = []): array
    {
        $this->commands[] = compact('command', 'attributes');

        return match ($command) {
            '/ppp/profile/print' => [[
                '.id' => '*PROFILE',
                'name' => 'inactive',
                'address-list' => '',
                'use-ipv6' => 'yes',
                'local-address' => '10.99.99.1',
                'dns-server' => '',
            ]],
            '/ip/dns/print' => [[
                'allow-remote-requests' => 'no',
            ]],
            '/ip/proxy/print' => [[
                'enabled' => $this->proxyEnabled ? 'yes' : 'no',
                'port' => '8080',
            ]],
            '/ppp/active/print' => $this->activeSession ? [[
                '.id' => '*ACTIVE',
                'name' => 'inactive-user',
                'address' => '10.99.99.10',
            ]] : [],
            '/ppp/secret/print' => $this->activeSession ? [[
                'name' => 'inactive-user',
                'profile' => 'inactive',
            ]] : [],
            '/ip/proxy/access/print',
            '/ip/firewall/nat/print',
            '/ip/firewall/filter/print',
            '/ip/firewall/address-list/print' => [],
            default => [],
        };
    }
}
