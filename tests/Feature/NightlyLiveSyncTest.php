<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserHasPermission;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\NightlyLiveSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NightlyLiveSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_olt_page_shows_and_saves_the_nightly_live_sync_schedule(): void
    {
        $user = User::factory()->create();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->get(route('olt-onus.index'))
            ->assertOk()
            ->assertSee('Nightly Full Live-Data Sync')
            ->assertSee('name="enabled" value="1" checked', false)
            ->assertSee('name="run_time"', false)
            ->assertSee('type="time"', false);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('olt-onus.nightly-live-sync-settings.update'), [
                'enabled' => '0',
                'run_time' => '05:00',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Nightly live-data sync disabled.');

        $this->assertSame('0', AppSetting::value(NightlyLiveSyncService::ENABLED_KEY));
        $this->assertSame('05:00', AppSetting::value(NightlyLiveSyncService::RUN_TIME_KEY));
    }

    public function test_nightly_sync_is_due_only_once_during_the_selected_hour(): void
    {
        $service = app(NightlyLiveSyncService::class);
        $service->setSchedule(true, '04:00');
        Carbon::setTestNow('2026-09-03 04:00:00');

        $this->assertTrue($service->isDue());

        AppSetting::setValue(NightlyLiveSyncService::LAST_STARTED_AT_KEY, now()->toDateTimeString());
        $this->assertFalse($service->isDue());

        Carbon::setTestNow('2026-09-04 04:00:00');
        $this->assertFalse($service->isDue());

        $service->setSchedule(true, '05:30');
        $this->assertFalse($service->isDue());

        Carbon::setTestNow('2026-09-04 05:30:00');
        $this->assertTrue($service->isDue());
    }

    public function test_nightly_sync_runs_every_read_only_live_refresh_step_and_records_success(): void
    {
        Carbon::setTestNow('2026-09-03 04:05:00');
        $service = app(NightlyLiveSyncService::class);
        $commands = [];

        $result = $service->run(function (string $command, array $parameters) use (&$commands): int {
            $commands[] = [$command, $parameters];

            return 0;
        });

        $this->assertSame([
            ['mikrotik:import-secrets', []],
            ['mikrotik:import-ip-pools', []],
            ['mikrotik:sync-active-macs', []],
            ['olt:sync-all', []],
        ], $commands);
        $this->assertSame('success', $result['status']);
        $this->assertSame(4, $result['succeeded']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame('success', AppSetting::value(NightlyLiveSyncService::LAST_STATUS_KEY));
        $this->assertNotNull(AppSetting::value(NightlyLiveSyncService::LAST_COMPLETED_AT_KEY));
    }

    public function test_disabled_nightly_sync_command_skips_without_touching_live_sources(): void
    {
        app(NightlyLiveSyncService::class)->setSchedule(false, '04:00');

        $this->artisan('network:nightly-live-sync')
            ->expectsOutputToContain('disabled')
            ->assertSuccessful();

        $this->assertNull(AppSetting::value(NightlyLiveSyncService::LAST_STARTED_AT_KEY));
    }
}
