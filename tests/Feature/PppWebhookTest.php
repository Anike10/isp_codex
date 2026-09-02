<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\OltOnu;
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
                    && str_contains($onDown, ':local webhookBytesIn $"bytes-in";')
                    && str_contains($onDown, ':local webhookBytesOut $"bytes-out";')
                    && str_contains($onDown, ':local webhookCallerId $"caller-id";')
                    && str_contains($onDown, ':local webhookReason $"last-disconnect-reason";')
                    && str_contains($onDown, '\"reason\":\"".$webhookReason."\"')
                    && str_contains($onDown, '\"download\":\"".$webhookBytesOut."\"')
                    && str_contains($onDown, '\"upload\":\"".$webhookBytesIn."\"')
                    && str_contains($onDown, 'http-data=$webhookPayload')
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

    public function test_saving_settings_stores_the_disconnect_log_retention_days(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);

        $this->actingAs($this->admin())
            ->patch(route('troubleshoot.webhook.update'), ['retention_days' => 45])
            ->assertRedirect();

        $this->assertSame(45, app(PppWebhookService::class)->retentionDays());
    }

    public function test_delete_old_rows_now_prunes_beyond_the_retention_window(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);

        PppUsageLog::create(['username' => 'old', 'download_bytes' => 0, 'upload_bytes' => 0, 'payload' => [], 'disconnected_at' => now()->subDays(40)]);
        PppUsageLog::create(['username' => 'recent', 'download_bytes' => 0, 'upload_bytes' => 0, 'payload' => [], 'disconnected_at' => now()->subDays(3)]);

        $this->actingAs($this->admin())
            ->patch(route('troubleshoot.webhook.update'), ['retention_days' => 7, 'action' => 'prune'])
            ->assertRedirect()
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Deleted 1'));

        $this->assertSame(['recent'], PppUsageLog::pluck('username')->all());
    }

    public function test_prune_command_deletes_old_rows_when_retention_is_set(): void
    {
        AppSetting::setValue(PppWebhookService::RETENTION_KEY, '10');
        PppUsageLog::create(['username' => 'stale', 'download_bytes' => 0, 'upload_bytes' => 0, 'payload' => [], 'disconnected_at' => now()->subDays(20)]);
        PppUsageLog::create(['username' => 'fresh', 'download_bytes' => 0, 'upload_bytes' => 0, 'payload' => [], 'disconnected_at' => now()->subDay()]);

        $this->artisan('ppp:prune-usage-logs')->assertSuccessful();

        $this->assertSame(['fresh'], PppUsageLog::pluck('username')->all());
    }

    public function test_prune_command_is_a_no_op_when_retention_is_off(): void
    {
        PppUsageLog::create(['username' => 'kept', 'download_bytes' => 0, 'upload_bytes' => 0, 'payload' => [], 'disconnected_at' => now()->subYears(3)]);

        $this->artisan('ppp:prune-usage-logs')->assertSuccessful();

        $this->assertSame(1, PppUsageLog::count());
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
        $this->assertSame('webhook', $log->source);
    }

    public function test_delayed_webhook_enriches_a_recent_listener_log_without_replacing_its_bytes(): void
    {
        $router = $this->restRouter('10.0.0.48');
        $log = PppUsageLog::create([
            'mikrotik_router_id' => $router->id,
            'username' => 'late-hook',
            'source' => 'listener',
            'routeros_session_id' => '*A3',
            'download_bytes' => 12345,
            'upload_bytes' => 6789,
            'payload' => ['ppp_active_snapshot' => ['bytes-out' => '12345']],
            'disconnected_at' => now(),
        ]);

        $this->withHeader(PppWebhookService::SECRET_HEADER, app(PppWebhookService::class)->secret())
            ->postJson('/api/ppp/usage', [
                'user' => 'late-hook',
                'router_id' => (string) $router->id,
                'download' => 0,
                'upload' => 0,
                'reason' => 'peer-request',
            ])
            ->assertCreated();

        $this->assertSame(1, PppUsageLog::count());
        $log->refresh();
        $this->assertSame('webhook+listener', $log->source);
        $this->assertSame(12345, $log->download_bytes);
        $this->assertSame(6789, $log->upload_bytes);
        $this->assertSame('peer-request', $log->disconnect_reason);
        $this->assertArrayHasKey('webhook', $log->payload);
    }

    public function test_webhook_copies_a_valid_caller_mac_onto_the_matched_party(): void
    {
        $router = $this->restRouter('10.0.0.25');
        $customer = Customer::create([
            'name' => 'MAC Repair User', 'phone' => '01700000025', 'connection_id' => 'mac-repair',
            'mikrotik_username' => 'mac-repair', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $this->withHeader(PppWebhookService::SECRET_HEADER, app(PppWebhookService::class)->secret())
            ->postJson('/api/ppp/usage', [
                'user' => 'mac-repair',
                'caller_id' => 'b8-3a-08-da-0c-5f',
                'router_id' => (string) $router->id,
            ])
            ->assertCreated();

        $customer->refresh();
        $this->assertSame('B8:3A:08:DA:0C:5F', $customer->last_connected_mac);
        $this->assertNotNull($customer->last_connected_at);
    }

    public function test_webhook_matches_the_onu_by_serial_mac_and_records_its_receiving_power(): void
    {
        $onu = OltOnu::create([
            'pon_port' => 1, 'onu_id' => 5, 'mac_address' => '00:8d:ff:02:2a:17',
            'rx_power_dbm' => -21.50, 'status' => 'online', 'name' => 'ONU-5',
        ]);
        $secret = app(PppWebhookService::class)->secret();

        $response = $this->withHeader(PppWebhookService::SECRET_HEADER, $secret)
            ->postJson('/api/ppp/usage', [
                'user' => 'pppoe-9', 'uptime' => '30m',
                'caller_id' => '00:8D:FF:02:2A:17', 'router_id' => '1',
                'reason' => 'peer-request',
            ])
            ->assertCreated();

        $response->assertJsonPath('onu_id', $onu->id);

        $log = PppUsageLog::firstOrFail();
        $this->assertSame($onu->id, $log->olt_onu_id);
        $this->assertSame('-21.50', (string) $log->rx_power_dbm);
        $this->assertSame('00:8D:FF:02:2A:17', $log->caller_id);
        $this->assertSame('peer-request', $log->disconnect_reason);
    }

    public function test_webhook_stores_null_when_no_disconnect_reason_is_sent(): void
    {
        $secret = app(PppWebhookService::class)->secret();

        $this->withHeader(PppWebhookService::SECRET_HEADER, $secret)
            ->postJson('/api/ppp/usage', ['user' => 'pppoe-noreason', 'caller_id' => '00:00:00:00:00:09'])
            ->assertCreated();

        $this->assertNull(PppUsageLog::firstOrFail()->disconnect_reason);
    }

    public function test_webhook_prefers_a_learned_device_mac_over_a_conflicting_onu_serial(): void
    {
        OltOnu::create([
            'pon_port' => 3, 'onu_id' => 18, 'mac_address' => '00:8d:ff:02:2a:17',
            'rx_power_dbm' => -17.77, 'status' => 'online',
            'last_live_polled_at' => now(),
        ]);
        $onu = OltOnu::create([
            'pon_port' => 2, 'onu_id' => 7, 'mac_address' => 'aa:aa:aa:aa:aa:aa',
            'learned_macs' => [['mac' => '00:8d:ff:02:2a:17', 'vlan' => 100]],
            'rx_power_dbm' => -30.00, 'status' => 'online',
            'last_live_polled_at' => now()->subHour(),
        ]);
        $secret = app(PppWebhookService::class)->secret();

        $this->withHeader(PppWebhookService::SECRET_HEADER, $secret)
            ->postJson('/api/ppp/usage', ['user' => 'pppoe-x', 'caller_id' => '008dff022a17'])
            ->assertCreated();

        $this->assertSame($onu->id, PppUsageLog::firstOrFail()->olt_onu_id);
    }

    public function test_webhook_falls_back_to_last_connected_mac_to_find_the_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Mac Party', 'phone' => '01700000001', 'connection_id' => 'mac-1',
            'mikrotik_username' => 'mac-1', 'last_connected_mac' => '00:8d:ff:02:2a:17',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        $secret = app(PppWebhookService::class)->secret();

        $this->withHeader(PppWebhookService::SECRET_HEADER, $secret)
            ->postJson('/api/ppp/usage', [
                'user' => 'not-a-known-username',
                'caller_id' => '00:8d:ff:02:2a:17',
            ])
            ->assertCreated();

        $this->assertSame($customer->id, PppUsageLog::firstOrFail()->customer_id);
    }

    public function test_webhook_mac_fallback_ignores_a_soft_deleted_party(): void
    {
        $customer = Customer::create([
            'name' => 'Gone Party', 'phone' => '01700000009', 'connection_id' => 'gone-1',
            'mikrotik_username' => 'gone-1', 'last_connected_mac' => '00:8D:FF:02:2A:17',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        $customer->delete();
        $secret = app(PppWebhookService::class)->secret();

        $this->withHeader(PppWebhookService::SECRET_HEADER, $secret)
            ->postJson('/api/ppp/usage', ['user' => 'unknown', 'caller_id' => '00:8d:ff:02:2a:17'])
            ->assertCreated();

        $this->assertNull(PppUsageLog::firstOrFail()->customer_id);
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
