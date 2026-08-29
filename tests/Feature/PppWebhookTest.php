<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\PppUsageLog;
use App\Models\User;
use App\Services\PppWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'view_network_diagnostics')->firstOrFail());

        return $user;
    }

    private function restRouter(string $ip, array $overrides = []): MikrotikRouter
    {
        return MikrotikRouter::create(array_merge([
            'name' => 'REST Router', 'ip_address' => $ip, 'api_port' => 8181,
            'transport' => 'rest', 'pppoe_sync_interval_minutes' => 60,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'anike', 'password' => 'reader-pass',
            'status' => 'active', 'read_only' => false,
        ], $overrides));
    }

    public function test_enabling_writes_one_on_down_script_to_every_ppp_profile(): void
    {
        Http::fake([
            '10.0.0.20:8181/rest/ppp/profile' => Http::response([
                ['.id' => '*1', 'name' => 'P1'],
                ['.id' => '*2', 'name' => 'P2'],
            ], 200),
            '10.0.0.20:8181/rest/ppp/profile/*' => Http::response([], 200),
        ]);

        // Only this router should be touched.
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $router = $this->restRouter('10.0.0.20');

        $this->actingAs($this->admin())
            ->patch(route('troubleshoot.webhook.update'), [
                'enabled' => '1',
                'url' => 'https://billing.example.com/api/ppp/usage',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('1', AppSetting::value(PppWebhookService::ENABLED_KEY));
        $this->assertSame('https://billing.example.com/api/ppp/usage', AppSetting::value(PppWebhookService::URL_KEY));
        $this->assertNotEmpty(AppSetting::value(PppWebhookService::SECRET_KEY));

        foreach (['*1', '*2'] as $id) {
            Http::assertSent(function ($request) use ($id, $router) {
                if ($request->method() !== 'PATCH' || ! str_ends_with($request->url(), "/rest/ppp/profile/{$id}")) {
                    return false;
                }
                $onDown = $request->data()['on-down'] ?? '';

                return str_contains($onDown, '/tool fetch')
                    && str_contains($onDown, 'https://billing.example.com/api/ppp/usage')
                    && str_contains($onDown, '\"router_id\":\"'.$router->id.'\"')
                    && str_contains($onDown, PppWebhookService::SECRET_HEADER);
            });
        }
    }

    public function test_disabling_clears_the_on_down_script_on_every_profile(): void
    {
        Http::fake([
            '10.0.0.21:8181/rest/ppp/profile' => Http::response([['.id' => '*7', 'name' => 'P']], 200),
            '10.0.0.21:8181/rest/ppp/profile/*' => Http::response([], 200),
        ]);

        MikrotikRouter::query()->update(['status' => 'inactive']);
        $this->restRouter('10.0.0.21');
        AppSetting::setValue(PppWebhookService::ENABLED_KEY, '1');
        AppSetting::setValue(PppWebhookService::URL_KEY, 'https://billing.example.com/api/ppp/usage');

        $this->actingAs($this->admin())
            ->patch(route('troubleshoot.webhook.update'), ['url' => 'https://billing.example.com/api/ppp/usage'])
            ->assertRedirect();

        $this->assertSame('0', AppSetting::value(PppWebhookService::ENABLED_KEY));

        Http::assertSent(fn ($request) => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/rest/ppp/profile/*7')
            && ($request->data()['on-down'] ?? 'x') === '');
    }

    public function test_read_only_and_inactive_routers_are_skipped_and_reported(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        MikrotikRouter::query()->update(['status' => 'inactive']);
        $this->restRouter('10.0.0.22', ['name' => 'Read Only', 'read_only' => true]);
        $this->restRouter('10.0.0.23', ['name' => 'Switched Off', 'status' => 'inactive']);

        $this->actingAs($this->admin())
            ->patch(route('troubleshoot.webhook.update'), [
                'enabled' => '1',
                'url' => 'https://billing.example.com/api/ppp/usage',
            ])
            ->assertRedirect()
            ->assertSessionHas('warning', fn ($m) => str_contains($m, 'Read Only') && str_contains($m, 'Switched Off'));

        Http::assertNothingSent();
    }

    public function test_url_is_required_when_enabling(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);

        $this->actingAs($this->admin())
            ->from(route('troubleshoot.webhook.edit'))
            ->patch(route('troubleshoot.webhook.update'), ['enabled' => '1', 'url' => ''])
            ->assertRedirect(route('troubleshoot.webhook.edit'))
            ->assertSessionHasErrors('url');
    }

    public function test_settings_page_requires_the_network_diagnostics_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('troubleshoot.webhook.edit'))->assertForbidden();
        $this->actingAs($plain)->patch(route('troubleshoot.webhook.update'), ['url' => ''])->assertForbidden();
    }

    public function test_webhook_endpoint_stores_usage_and_links_router_and_customer(): void
    {
        $router = $this->restRouter('10.0.0.24');
        $customer = Customer::create([
            'name' => 'Wire User', 'phone' => '01700000000', 'connection_id' => 'wire-1',
            'mikrotik_username' => 'wire-1', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        $secret = app(PppWebhookService::class)->secret();

        $this->withHeader(PppWebhookService::SECRET_HEADER, $secret)
            ->postJson('/api/ppp/usage', [
                'user' => 'wire-1',
                'uptime' => '1h2m3s',
                'download' => '10485760',
                'upload' => '2097152',
                'router_id' => (string) $router->id,
            ])
            ->assertCreated();

        $log = PppUsageLog::firstOrFail();
        $this->assertSame('wire-1', $log->username);
        $this->assertSame($router->id, $log->mikrotik_router_id);
        $this->assertSame($customer->id, $log->customer_id);
        $this->assertSame(3723, $log->uptime_seconds);
        $this->assertSame(10485760, $log->download_bytes);
        $this->assertSame(2097152, $log->upload_bytes);
    }

    public function test_webhook_endpoint_rejects_a_missing_or_wrong_secret(): void
    {
        app(PppWebhookService::class)->secret();

        $this->postJson('/api/ppp/usage', ['user' => 'x', 'router_id' => '1'])
            ->assertStatus(401);

        $this->withHeader(PppWebhookService::SECRET_HEADER, 'nope')
            ->postJson('/api/ppp/usage', ['user' => 'x', 'router_id' => '1'])
            ->assertStatus(401);

        $this->assertSame(0, PppUsageLog::count());
    }
}
