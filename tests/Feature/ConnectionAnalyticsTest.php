<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\PppUsageLog;
use App\Models\User;
use App\Services\PppWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function seer(): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'view_network_diagnostics')->firstOrFail());

        return $user;
    }

    private function logDisconnect(string $username, string $when, ?int $routerId = null, ?float $rxPower = null, ?string $callerId = null): void
    {
        PppUsageLog::create([
            'mikrotik_router_id' => $routerId,
            'username' => $username,
            'caller_id' => $callerId,
            'download_bytes' => 0,
            'upload_bytes' => 0,
            'rx_power_dbm' => $rxPower,
            'payload' => [],
            'disconnected_at' => $when,
        ]);
    }

    public function test_frequent_mac_changes_lists_users_over_the_distinct_mac_threshold(): void
    {
        // roamer: 3 different MACs in the window
        $this->logDisconnect('roamer', now()->subHours(1)->toDateTimeString(), null, null, '00:00:00:00:00:01');
        $this->logDisconnect('roamer', now()->subHours(3)->toDateTimeString(), null, null, '00:00:00:00:00:02');
        $this->logDisconnect('roamer', now()->subHours(5)->toDateTimeString(), null, null, '00:00:00:00:00:03');
        $this->logDisconnect('roamer', now()->subHours(6)->toDateTimeString(), null, null, '00:00:00:00:00:03');

        // steady: many disconnects but always the same MAC
        for ($i = 0; $i < 6; $i++) {
            $this->logDisconnect('steady', now()->subHours($i + 1)->toDateTimeString(), null, null, 'AA:AA:AA:AA:AA:AA');
        }
        // old: 4 MACs but 30h ago — outside a 24h window
        for ($i = 1; $i <= 4; $i++) {
            $this->logDisconnect('old', now()->subHours(30 + $i)->toDateTimeString(), null, null, "00:00:00:00:0F:0{$i}");
        }

        $seer = $this->seer();

        $response = $this->actingAs($seer)->get(route('troubleshoot.mac-changes', ['hours' => 24, 'min_macs' => 3]))
            ->assertOk()
            ->assertSee('roamer')
            ->assertSee('00:00:00:00:00:02')
            ->assertDontSee('steady')
            ->assertDontSee('>old<', false)
            ->assertSee('1 user(s) over threshold');

        // Newest MAC listed before the older one.
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, '00:00:00:00:00:03'), strpos($html, '00:00:00:00:00:01'));
    }

    public function test_frequent_mac_changes_requires_the_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('troubleshoot.mac-changes'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->patch(route('troubleshoot.retention'), ['retention_days' => 5])
            ->assertForbidden();
    }

    public function test_retention_can_be_set_and_applied_from_a_report_page(): void
    {
        $seer = $this->seer();

        PppUsageLog::create(['username' => 'old', 'download_bytes' => 0, 'upload_bytes' => 0, 'payload' => [], 'disconnected_at' => now()->subDays(40)]);
        PppUsageLog::create(['username' => 'new', 'download_bytes' => 0, 'upload_bytes' => 0, 'payload' => [], 'disconnected_at' => now()->subDay()]);

        // Save only.
        $this->actingAs($seer)->patch(route('troubleshoot.retention'), ['retention_days' => 14])->assertRedirect();
        $this->assertSame(14, app(PppWebhookService::class)->retentionDays());
        $this->assertSame(2, PppUsageLog::count());

        // The control shows the saved value on every report page.
        $this->actingAs($seer)->get(route('troubleshoot.mac-changes'))
            ->assertOk()->assertSee('name="retention_days" min="0" max="3650" value="14"', false);
        $this->actingAs($seer)->get(route('troubleshoot.frequent-disconnects'))
            ->assertOk()->assertSee('name="retention_days" min="0" max="3650" value="14"', false);

        // Delete now.
        $this->actingAs($seer)->patch(route('troubleshoot.retention'), ['retention_days' => 7, 'action' => 'prune'])
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Deleted 1'));
        $this->assertSame(['new'], PppUsageLog::pluck('username')->all());
    }

    public function test_frequent_mac_changes_make_default_persists_the_filters(): void
    {
        $seer = $this->seer();

        $this->actingAs($seer)
            ->get(route('troubleshoot.mac-changes', ['hours' => 72, 'min_macs' => 5, 'make_default' => 1]))
            ->assertOk()
            ->assertSee('Saved &mdash; this page now opens with these filters.', false);

        // A later bare visit opens with the saved filters.
        $this->actingAs($seer)
            ->get(route('troubleshoot.mac-changes'))
            ->assertOk()
            ->assertSee('name="hours" min="1" max="8760" value="72"', false)
            ->assertSee('name="min_macs" min="2" max="100" value="5"', false)
            ->assertDontSee('Saved &mdash; this page now opens', false);
    }

    public function test_frequent_disconnects_make_default_persists_the_filters(): void
    {
        $seer = $this->seer();

        $this->actingAs($seer)
            ->get(route('troubleshoot.frequent-disconnects', ['hours' => 48, 'min_count' => 25, 'make_default' => 1]))
            ->assertOk()
            ->assertSee('Saved', false);

        $this->actingAs($seer)
            ->get(route('troubleshoot.frequent-disconnects'))
            ->assertOk()
            ->assertSee('name="hours" min="1" max="8760" value="48"', false)
            ->assertSee('name="min_count" min="1" max="10000" value="25"', false);
    }

    public function test_connection_analytics_make_default_persists_sort_and_filters(): void
    {
        $seer = $this->seer();

        $this->actingAs($seer)
            ->get(route('troubleshoot.analytics', ['sort' => 'd24h', 'dir' => 'asc', 'search' => 'north', 'make_default' => 1]))
            ->assertOk()
            ->assertSee('Saved', false);

        $this->actingAs($seer)
            ->get(route('troubleshoot.analytics'))
            ->assertOk()
            ->assertSee('name="sort" value="d24h"', false)
            ->assertSee('name="dir" value="asc"', false)
            ->assertSee('name="search" value="north"', false);
    }

    public function test_all_three_reports_show_the_latest_onu_rx_and_tx_power_for_a_user(): void
    {
        // Two distinct MACs so the same user also crosses the MAC-changes threshold.
        $this->logDisconnect('fiber-1', now()->subDays(2)->toDateTimeString(), null, -19.00, '00:00:00:00:00:01');
        $this->logDisconnect('fiber-1', now()->subHours(3)->toDateTimeString(), null, null, '00:00:00:00:00:02');
        PppUsageLog::create([
            'username' => 'fiber-1', 'caller_id' => '00:00:00:00:00:03',
            'download_bytes' => 0, 'upload_bytes' => 0, 'payload' => [],
            'rx_power_dbm' => -27.40, 'tx_power_dbm' => 2.15, 'disconnected_at' => now()->subHour(),
        ]);

        $seer = $this->seer();

        foreach ([
            route('troubleshoot.analytics'),
            route('troubleshoot.frequent-disconnects', ['min_count' => 1]),
            route('troubleshoot.mac-changes', ['min_macs' => 2]),
        ] as $url) {
            $this->actingAs($seer)->get($url)
                ->assertOk()
                ->assertSee('ONU power (Rx / Tx)')
                ->assertSee('Rx -27.40')
                ->assertSee('Tx 2.15');
        }
    }

    public function test_frequent_disconnects_lists_only_users_over_the_threshold_in_the_window(): void
    {
        // flapping: 4 drops in the last hour
        for ($i = 0; $i < 4; $i++) {
            $this->logDisconnect('flapper', now()->subMinutes(10 * $i + 1)->toDateTimeString());
        }
        // stable: 1 recent drop + 5 old drops (outside a 24h window)
        $this->logDisconnect('stable', now()->subHour()->toDateTimeString());
        for ($i = 0; $i < 5; $i++) {
            $this->logDisconnect('stable', now()->subDays(3)->subHours($i)->toDateTimeString());
        }

        $this->actingAs($this->seer())
            ->get(route('troubleshoot.frequent-disconnects', ['hours' => 24, 'min_count' => 3]))
            ->assertOk()
            ->assertSee('flapper')
            ->assertDontSee('stable')
            ->assertSee('1 user(s) over threshold');

        // A looser threshold pulls "stable" in too.
        $this->actingAs($this->seer())
            ->get(route('troubleshoot.frequent-disconnects', ['hours' => 24, 'min_count' => 1]))
            ->assertOk()
            ->assertSee('flapper')
            ->assertSee('stable');
    }

    public function test_connection_analytics_counts_each_time_window_and_sorts(): void
    {
        $this->logDisconnect('alice', now()->subHours(2)->toDateTimeString());
        $this->logDisconnect('alice', now()->subDays(3)->toDateTimeString());
        $this->logDisconnect('alice', now()->subDays(20)->toDateTimeString());
        $this->logDisconnect('alice', now()->subDays(200)->toDateTimeString());

        $this->logDisconnect('bob', now()->subHours(1)->toDateTimeString());
        $this->logDisconnect('bob', now()->subHours(5)->toDateTimeString());

        $seer = $this->seer();

        $default = $this->actingAs($seer)->get(route('troubleshoot.analytics'));
        $default->assertOk()->assertSee('alice')->assertSee('bob')->assertSee('2 user(s)');

        // Default sort is all-time desc: alice (4) before bob (2).
        $html = $default->getContent();
        $this->assertLessThan(strpos($html, '>bob<'), strpos($html, '>alice<'));

        // Sort by 24h desc: bob (2) before alice (1).
        $byDay = $this->actingAs($seer)->get(route('troubleshoot.analytics', ['sort' => 'd24h', 'dir' => 'desc']))->getContent();
        $this->assertLessThan(strpos($byDay, '>alice<'), strpos($byDay, '>bob<'));
    }

    public function test_analytics_search_and_router_filter_narrow_the_list(): void
    {
        $routerA = MikrotikRouter::create([
            'name' => 'Router A', 'ip_address' => '10.9.0.1', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);

        $this->logDisconnect('north-1', now()->subHour()->toDateTimeString(), $routerA->id);
        $this->logDisconnect('south-9', now()->subHour()->toDateTimeString(), null);

        $seer = $this->seer();

        $this->actingAs($seer)->get(route('troubleshoot.analytics', ['search' => 'north']))
            ->assertOk()->assertSee('north-1')->assertDontSee('south-9');

        $this->actingAs($seer)->get(route('troubleshoot.analytics', ['router' => $routerA->id]))
            ->assertOk()->assertSee('north-1')->assertDontSee('south-9');
    }

    public function test_troubleshoot_pages_require_the_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('troubleshoot.frequent-disconnects'))->assertForbidden();
        $this->actingAs($plain)->get(route('troubleshoot.analytics'))->assertForbidden();
    }

    public function test_frequent_disconnects_links_the_matching_party(): void
    {
        Customer::create([
            'name' => 'Flapper Party', 'phone' => '01700000000', 'connection_id' => 'flapper',
            'mikrotik_username' => 'flapper', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        for ($i = 0; $i < 12; $i++) {
            $this->logDisconnect('flapper', now()->subMinutes($i + 1)->toDateTimeString());
        }

        $this->actingAs($this->seer())
            ->get(route('troubleshoot.frequent-disconnects'))
            ->assertOk()
            ->assertSee('Flapper Party');
    }
}
