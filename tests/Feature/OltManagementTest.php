<?php

namespace Tests\Feature;

use App\Jobs\RunOltFullRefresh;
use App\Models\OltDevice;
use App\Models\OltOnu;
use App\Models\OltProtocolProfile;
use App\Models\OltRefreshRun;
use App\Models\User;
use App\Http\Middleware\EnsureUserHasPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OltManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_profile_mismatch_and_all_cached_pon_counts(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $this->onu($olt, 1, 1, 'online');
        $this->onu($olt, 2, 1, 'offline');
        $this->onu($olt, 3, 1, 'online');

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->get(route('olt-onus.index', ['olt_device_id' => $olt->id]))
            ->assertOk()
            ->assertSee('Profile mismatch')
            ->assertSee('Repair Profile Commands')
            ->assertSee('1 (1)')
            ->assertSee('2 (1)')
            ->assertSee('3 (1)')
            ->assertSee('all PONs with cached data — PON 1, 2, 3')
            ->assertSee('Select Ethernet port')
            ->assertSee('ONU / OLT / Name')
            ->assertSee('style="min-width:380px"', false)
            ->assertSee('style="width:420px; max-width:70vw; padding:9px"', false)
            ->assertSee('App supports up to 255 characters')
            ->assertSee('Serial / Device MACs')
            ->assertSee('Power / Status')
            ->assertSee('Ethernet Port')
            ->assertSee('data-port-control-form', false)
            ->assertSee('Save All Current Lasers to Notes')
            ->assertSee('+leaser')
            ->assertDontSee('>Type<', false)
            ->assertSee('Download Config Backup')
            ->assertSee(route('olt-onus.olts.config-backup', $olt), false)
            ->assertSee('data-background-refresh-form', false)
            ->assertSee('name="refresh_mode" value="full_mac"', false);
    }

    public function test_profile_defaults_repair_gpon_commands_and_all_eight_pon_ports(): void
    {
        $olt = $this->gponOltWithEponCommands();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->post(route('olt-onus.olts.apply-profile-defaults', $olt))
            ->assertRedirect(route('olt-onus.index', ['olt_device_id' => $olt->id]));

        $olt->refresh();

        $this->assertSame("enable\nconfig", $olt->read_context_commands);
        $this->assertSame(implode(',', range(1, 8)), $olt->pon_ports);
        $this->assertSame('show ont-info all', $olt->onu_status_command);
        $this->assertSame('show ont-optical all', $olt->onu_power_command);
        $this->assertSame('show service-port all', $olt->onu_vlan_command);
        $this->assertSame('show mac-address all', $olt->onu_mac_command);
    }

    public function test_epon_vlan_editor_shows_transparent_mode(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $olt->update([
            'name' => 'Test EPON',
            'protocol_profile' => 'hsgq_epon',
        ]);
        $this->onu($olt, 2, 7, 'online');

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->get(route('olt-onus.index', ['olt_device_id' => $olt->id]))
            ->assertOk()
            ->assertSee('<option value="transparent"', false)
            ->assertSee('Transparent');
    }

    public function test_ethernet_port_control_uses_detected_count_and_never_shows_unknown(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $olt->update(['protocol_profile' => 'hsgq_epon']);
        $onu = $this->onu($olt, 2, 7, 'online');
        $onu->update([
            'port_vlans' => [
                ['port' => 1, 'mode' => 'tag', 'vlan' => 22],
                ['port' => 2, 'mode' => 'transparent', 'vlan' => null],
            ],
            'port_admin_states' => ['2' => 'disabled'],
        ]);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->get(route('olt-onus.index', ['olt_device_id' => $olt->id]))
            ->assertOk()
            ->assertSee('Port 1 — Enabled')
            ->assertSee('Port 2 — Disabled')
            ->assertDontSee('Port 3 —')
            ->assertDontSee('Current state: Unknown');
    }

    public function test_gpon_ethernet_port_control_uses_capability_count(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $fourPortOnu = $this->onu($olt, 1, 1, 'online');
        $veipOnlyOnu = $this->onu($olt, 1, 2, 'online');
        $fourPortOnu->update(['ethernet_port_count' => 4]);
        $veipOnlyOnu->update(['ethernet_port_count' => 0]);

        $this->assertSame([1, 2, 3, 4], $fourPortOnu->fresh()->ethernetPortNumbers());
        $this->assertSame([], $veipOnlyOnu->fresh()->ethernetPortNumbers());

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->get(route('olt-onus.index', ['olt_device_id' => $olt->id]))
            ->assertOk()
            ->assertSee('Port 4 — Enabled')
            ->assertDontSee('Port 5 —')
            ->assertSee('No controllable Ethernet port');
    }

    public function test_refresh_error_can_be_cleared_without_deleting_cached_onus(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $onu = $this->onu($olt, 1, 1, 'online');
        $olt->update(['last_error' => 'Old error', 'last_raw_output' => 'Old output']);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->delete(route('olt-onus.olts.cached-onus.destroy', $olt))
            ->assertRedirect(route('olt-onus.index', ['olt_device_id' => $olt->id]));

        $this->assertDatabaseHas('olt_devices', ['id' => $olt->id]);
        $this->assertDatabaseHas('olt_onus', ['id' => $onu->id, 'olt_device_id' => $olt->id]);
        $this->assertNull($olt->fresh()->last_error);
        $this->assertNull($olt->fresh()->last_raw_output);
    }

    public function test_index_distinguishes_configured_active_from_a_failed_olt_connection(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $olt->update([
            'status' => 'active',
            'last_error' => 'Cannot connect to OLT 103.133.200.180:23. Connection refused',
        ]);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->get(route('olt-onus.index', ['olt_device_id' => $olt->id]))
            ->assertOk()
            ->assertSee('Configured Active')
            ->assertSee('Connection failed')
            ->assertSee('OLT could not be reached: Cannot connect to OLT 103.133.200.180:23. Connection refused');
    }

    public function test_deleting_an_olt_also_deletes_its_cached_onus(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $this->onu($olt, 1, 1, 'online');

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->delete(route('olt-onus.olts.destroy', $olt))
            ->assertRedirect(route('olt-onus.index'));

        $this->assertDatabaseMissing('olt_devices', ['id' => $olt->id]);
        $this->assertDatabaseMissing('olt_onus', ['olt_device_id' => $olt->id]);
    }

    public function test_full_refresh_is_queued_and_returns_a_progress_endpoint(): void
    {
        Queue::fake();
        $olt = $this->gponOltWithEponCommands();

        $response = $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->postJson(route('olt-onus.olts.refresh', $olt), [
                'refresh_mode' => 'full_mac',
                'pon_port' => '',
            ])
            ->assertAccepted()
            ->assertJsonPath('run.status', 'queued')
            ->assertJsonPath('run.progress', 0);

        $runId = $response->json('run.id');
        Queue::assertPushed(
            RunOltFullRefresh::class,
            fn (RunOltFullRefresh $job) => $job->refreshRunId === $runId && $job->connection === 'deferred'
        );

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->getJson($response->json('run.status_url'))
            ->assertOk()
            ->assertJsonPath('id', $runId)
            ->assertJsonPath('status', 'queued')
            ->assertJsonPath('progress', 0);
    }

    public function test_duplicate_full_refresh_reuses_the_active_run(): void
    {
        Queue::fake();
        $olt = $this->gponOltWithEponCommands();
        $user = User::factory()->create();

        $first = $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->postJson(route('olt-onus.olts.refresh', $olt), ['refresh_mode' => 'full_mac'])
            ->assertAccepted();

        $second = $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->postJson(route('olt-onus.olts.refresh', $olt), ['refresh_mode' => 'full_mac'])
            ->assertAccepted();

        $this->assertSame($first->json('run.id'), $second->json('run.id'));
        $this->assertDatabaseCount('olt_refresh_runs', 1);
        Queue::assertPushed(RunOltFullRefresh::class, 1);
    }

    public function test_current_laser_is_appended_after_existing_onu_note(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $onu = $this->onu($olt, 2, 35, 'online');
        $onu->update(['note' => 'Existing note', 'rx_power_dbm' => -13.58]);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->postJson(route('olt-onus.note.current-laser.store', $onu))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $note = $onu->fresh()->note;
        $this->assertStringStartsWith("Existing note\n", $note);
        $this->assertStringContainsString(' | Laser: -13.58 dBm', $note);
    }

    public function test_bulk_current_laser_appends_one_timestamped_line_to_every_onu_note(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $withPower = $this->onu($olt, 1, 1, 'online');
        $withoutPower = $this->onu($olt, 1, 2, 'offline');
        $withPower->update(['note' => 'Before', 'rx_power_dbm' => -18.25]);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->post(route('olt-onus.notes.current-laser.store'))
            ->assertRedirect();

        $this->assertStringContainsString("Before\n", $withPower->fresh()->note);
        $this->assertStringContainsString(' | Laser: -18.25 dBm', $withPower->fresh()->note);
        $this->assertStringContainsString(' | Laser: N/A (no live power)', $withoutPower->fresh()->note);
    }

    public function test_background_job_records_an_inactive_olt_failure_without_network_access(): void
    {
        $olt = $this->gponOltWithEponCommands();
        $olt->update(['status' => 'inactive']);
        $run = OltRefreshRun::query()->create([
            'olt_device_id' => $olt->id,
            'olt_name' => $olt->name,
            'refresh_mode' => 'full_mac',
            'status' => 'queued',
            'progress' => 0,
        ]);

        app()->call([new RunOltFullRefresh($run->id), 'handle']);

        $run->refresh();
        $this->assertSame('failed', $run->status);
        $this->assertSame(100, $run->progress);
        $this->assertStringContainsString('inactive', $run->message);
        $this->assertNotNull($run->completed_at);
    }

    private function gponOltWithEponCommands(): OltDevice
    {
        $profile = OltProtocolProfile::query()->where('key', 'hsgq_gpon')->firstOrFail();

        return OltDevice::query()->create([
            'name' => 'Test GPON',
            'brand' => 'HSGQ',
            'protocol_profile' => $profile->key,
            'host' => '192.0.2.10',
            'access_method' => 'ssh',
            'port' => 22,
            'username' => 'test',
            'password' => 'secret',
            'read_context_commands' => "enable\nconfig",
            'pon_ports' => '1,2',
            'onu_status_command' => 'show onu-info all',
            'onu_power_command' => 'show optical-info',
            'onu_alarm_command' => 'show onu-info-alarm {onu_id}',
            'onu_vlan_command' => 'show port-vlan',
            'onu_mac_command' => 'show mac-address epon all',
            'status' => 'active',
        ]);
    }

    private function onu(OltDevice $olt, int $pon, int $onuId, string $status): OltOnu
    {
        return OltOnu::query()->create([
            'olt_device_id' => $olt->id,
            'olt_name' => $olt->name,
            'pon_port' => $pon,
            'onu_id' => $onuId,
            'status' => $status,
        ]);
    }
}
