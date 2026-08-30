<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserHasPermission;
use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\CustomerOnuPowerSample;
use App\Models\OltOnu;
use App\Models\Permission;
use App\Models\User;
use App\Services\OnuPowerHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OnuPowerHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function party(string $mac, string $connectionId = 'ONU-1'): Customer
    {
        return Customer::create([
            'name' => 'Signal Party',
            'phone' => '0170000'.substr(md5($connectionId), 0, 4),
            'connection_id' => $connectionId,
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'last_connected_mac' => $mac,
        ]);
    }

    private function onu(array $overrides = []): OltOnu
    {
        return OltOnu::query()->create(array_replace([
            'olt_name' => 'US_EPON',
            'pon_port' => 5,
            'onu_id' => 19,
            'mac_address' => 'AA:BB:CC:DD:EE:01',
            'rx_power_dbm' => -21.40,
            'tx_power_dbm' => 2.15,
            'status' => 'online',
            'last_live_polled_at' => now(),
        ], $overrides));
    }

    public function test_capture_stores_one_sample_per_matched_party(): void
    {
        $this->party('AA:BB:CC:DD:EE:01');
        $this->onu();

        $result = app(OnuPowerHistoryService::class)->capture();

        $this->assertSame(1, $result['sampled']);
        $this->assertSame(1, CustomerOnuPowerSample::count());

        $sample = CustomerOnuPowerSample::first();
        $this->assertSame('-21.40', (string) $sample->rx_power_dbm);
        $this->assertSame('2.15', (string) $sample->tx_power_dbm);
        $this->assertSame('online', $sample->status);
        $this->assertNotNull($sample->sampled_at);
    }

    public function test_capture_skips_party_whose_onu_has_no_optical_reading(): void
    {
        $this->party('AA:BB:CC:DD:EE:01');
        $this->onu(['rx_power_dbm' => null, 'tx_power_dbm' => null]);

        $result = app(OnuPowerHistoryService::class)->capture();

        $this->assertSame(0, $result['sampled']);
        $this->assertSame(0, CustomerOnuPowerSample::count());
    }

    public function test_is_due_respects_the_configured_interval(): void
    {
        $service = app(OnuPowerHistoryService::class);
        $service->setIntervalHours(6);

        $this->assertTrue($service->isDue(), 'due when there are no samples yet');

        CustomerOnuPowerSample::create([
            'customer_id' => $this->party('AA:BB:CC:DD:EE:01')->id,
            'rx_power_dbm' => -20,
            'sampled_at' => now()->subHours(2),
            'created_at' => now()->subHours(2),
        ]);
        $this->assertFalse($service->isDue(), 'not due 2h after the last sample when interval is 6h');

        CustomerOnuPowerSample::query()->update([
            'sampled_at' => now()->subHours(7),
        ]);
        $this->assertTrue($service->isDue(), 'due again once the interval has elapsed');
    }

    public function test_command_skips_when_not_due_and_runs_with_force(): void
    {
        $this->party('AA:BB:CC:DD:EE:01');
        $this->onu();

        $service = app(OnuPowerHistoryService::class);
        $service->setIntervalHours(12);
        $service->capture(); // seed a fresh sample so the next run is not due

        Artisan::call('onu:capture-power-history');
        $this->assertStringContainsString('Skipped', Artisan::output());
        $this->assertSame(1, CustomerOnuPowerSample::count());

        Artisan::call('onu:capture-power-history', ['--force' => true]);
        $this->assertSame(2, CustomerOnuPowerSample::count());
    }

    public function test_prune_deletes_samples_older_than_retention(): void
    {
        $customer = $this->party('AA:BB:CC:DD:EE:01');
        $service = app(OnuPowerHistoryService::class);
        $service->setRetentionDays(7);

        CustomerOnuPowerSample::create([
            'customer_id' => $customer->id, 'rx_power_dbm' => -20,
            'sampled_at' => now()->subDays(2), 'created_at' => now()->subDays(2),
        ]);
        CustomerOnuPowerSample::create([
            'customer_id' => $customer->id, 'rx_power_dbm' => -20,
            'sampled_at' => now()->subDays(30), 'created_at' => now()->subDays(30),
        ]);

        $this->assertSame(1, $service->prune());
        $this->assertSame(1, CustomerOnuPowerSample::count());
    }

    public function test_settings_form_persists_interval_and_retention(): void
    {
        $user = User::factory()->create();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('olt-onus.power-history-settings.update'), [
                'interval_hours' => 4,
                'retention_days' => 21,
                'show_rx' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('4', AppSetting::value(OnuPowerHistoryService::INTERVAL_KEY));
        $this->assertSame('21', AppSetting::value(OnuPowerHistoryService::RETENTION_KEY));
    }

    public function test_show_rx_defaults_on_and_show_tx_defaults_off(): void
    {
        $service = app(OnuPowerHistoryService::class);

        $this->assertTrue($service->showRx());
        $this->assertFalse($service->showTx());
    }

    public function test_settings_form_persists_the_rx_tx_graph_checkboxes(): void
    {
        $user = User::factory()->create();
        $service = app(OnuPowerHistoryService::class);

        // Both ticked.
        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('olt-onus.power-history-settings.update'), [
                'interval_hours' => 1, 'retention_days' => 7,
                'show_rx' => '1', 'show_tx' => '1',
            ])->assertRedirect();
        $this->assertTrue($service->showRx());
        $this->assertTrue($service->showTx());

        // An unchecked box sends nothing -> stored as off.
        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('olt-onus.power-history-settings.update'), [
                'interval_hours' => 1, 'retention_days' => 7,
                'show_tx' => '1',
            ])->assertRedirect();
        $this->assertFalse($service->showRx());
        $this->assertTrue($service->showTx());
    }

    public function test_party_graph_hides_the_tx_series_when_the_setting_is_off(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = $this->party('AA:BB:CC:DD:EE:01', 'ONU-RXONLY');

        foreach (range(1, 3) as $i) {
            CustomerOnuPowerSample::create([
                'customer_id' => $customer->id,
                'rx_power_dbm' => -20 - $i,
                'tx_power_dbm' => 2 + $i,
                'status' => 'online',
                'sampled_at' => now()->subHours(3 * (4 - $i)),
                'created_at' => now(),
            ]);
        }

        // Default: Rx shown, Tx hidden.
        $body = $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Rx (dBm)')
            ->assertDontSee('Tx (dBm)')
            ->getContent();
        $this->assertSame(1, substr_count($body, '<polyline'));

        // Turn Tx on -> both series render.
        app(OnuPowerHistoryService::class)->setShowTx(true);
        $body = $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Tx (dBm)')
            ->getContent();
        $this->assertSame(2, substr_count($body, '<polyline'));
    }

    public function test_settings_form_can_capture_now(): void
    {
        $this->party('AA:BB:CC:DD:EE:01');
        $this->onu();
        $user = User::factory()->create();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('olt-onus.power-history-settings.update'), [
                'interval_hours' => 4,
                'retention_days' => 21,
                'action' => 'capture',
            ])
            ->assertRedirect();

        $this->assertSame(1, CustomerOnuPowerSample::count());
    }

    public function test_party_page_shows_the_signal_history_card_with_samples(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = $this->party('AA:BB:CC:DD:EE:01', 'ONU-CARD');

        foreach (range(1, 4) as $i) {
            CustomerOnuPowerSample::create([
                'customer_id' => $customer->id,
                'rx_power_dbm' => -20 - $i,
                'tx_power_dbm' => 2 + ($i / 10),
                'status' => 'online',
                'sampled_at' => now()->subHours(4 * (5 - $i)),
                'created_at' => now(),
            ]);
        }

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('ONU সিগন্যাল হিস্টোরি')
            ->assertSee('<polyline', false);
    }

    public function test_party_page_draws_the_chart_from_a_single_sample(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = $this->party('AA:BB:CC:DD:EE:01', 'ONU-ONE');

        CustomerOnuPowerSample::create([
            'customer_id' => $customer->id,
            'rx_power_dbm' => -21.5,
            'tx_power_dbm' => 2.5,
            'status' => 'online',
            'sampled_at' => now(),
            'created_at' => now(),
        ]);

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('<circle', false)
            ->assertDontSee('এখনো কোনো নমুনা জমা হয়নি');
    }
}
