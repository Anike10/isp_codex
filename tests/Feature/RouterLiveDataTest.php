<?php

namespace Tests\Feature;

use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouterLiveDataTest extends TestCase
{
    use RefreshDatabase;

    private function seer(): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'view_network_diagnostics')->firstOrFail());

        return $user->fresh();
    }

    private function restRouter(string $name = 'Edge REST', string $ip = '10.9.9.9'): MikrotikRouter
    {
        return MikrotikRouter::create([
            'name' => $name, 'ip_address' => $ip, 'api_port' => 8181, 'transport' => 'rest',
            'inactive_pppoe_profile' => 'inactive', 'username' => 'api', 'password' => 'secret',
            'status' => 'active', 'read_only' => true,
        ]);
    }

    public function test_page_requires_the_network_diagnostics_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('troubleshoot.router-data'))->assertForbidden();
    }

    public function test_it_shows_curated_live_sections_grouped_per_active_router(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $this->restRouter();

        Http::fake([
            '10.9.9.9:8181/rest/system/resource' => Http::response([
                'version' => '7.15.3', 'board-name' => 'CCR2004-1G-12S+2XS', 'uptime' => '1w2d3h', 'cpu-load' => '7',
            ], 200),
            '10.9.9.9:8181/rest/ppp/active' => Http::response([
                ['.id' => '*1', 'name' => 'pppoe-501', 'address' => '10.20.0.5', 'caller-id' => '00:8D:FF:02:2A:17'],
            ], 200),
            '10.9.9.9:8181/rest/*' => Http::response([], 200),
        ]);

        $this->actingAs($this->seer())->get(route('troubleshoot.router-data'))
            ->assertOk()
            ->assertSee('Router Live Data')
            ->assertSee('Edge REST')
            ->assertSee('System resource')
            ->assertSee('CCR2004-1G-12S+2XS')
            ->assertSee('7.15.3')
            ->assertSee('PPP active')
            ->assertSee('pppoe-501')
            ->assertSee('/ip/dhcp-server/lease/print');
    }

    public function test_it_reports_a_failed_section_without_dropping_the_others(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $this->restRouter();

        Http::fake([
            '10.9.9.9:8181/rest/ip/route' => Http::response('kaboom', 500),
            '10.9.9.9:8181/rest/system/resource' => Http::response(['version' => '7.15.3'], 200),
            '10.9.9.9:8181/rest/*' => Http::response([], 200),
        ]);

        $this->actingAs($this->seer())->get(route('troubleshoot.router-data'))
            ->assertOk()
            ->assertSee('7.15.3')
            ->assertSee('Routes')
            ->assertSee('HTTP 500');
    }

    public function test_the_extra_command_box_rejects_non_print_paths(): void
    {
        $this->restRouter();
        Http::fake(['10.9.9.9:8181/rest/*' => Http::response([], 200)]);

        $this->actingAs($this->seer())
            ->get(route('troubleshoot.router-data', ['command' => '/ppp/secret/remove']))
            ->assertOk()
            ->assertSee('read-only path that ends in /print')
            ->assertDontSee('Custom: /ppp/secret/remove');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/rest/ppp/secret'));
    }

    public function test_the_extra_command_box_runs_a_valid_print_path_on_every_router(): void
    {
        MikrotikRouter::query()->update(['status' => 'inactive']);
        $this->restRouter();

        Http::fake([
            '10.9.9.9:8181/rest/queue/simple' => Http::response([
                ['.id' => '*1', 'name' => 'q-office', 'target' => '10.20.0.0/24', 'max-limit' => '50M/50M'],
            ], 200),
            '10.9.9.9:8181/rest/*' => Http::response([], 200),
        ]);

        $this->actingAs($this->seer())
            ->get(route('troubleshoot.router-data', ['command' => '/queue/simple/print']))
            ->assertOk()
            ->assertSee('Custom: /queue/simple/print')
            ->assertSee('q-office')
            ->assertSee('50M/50M');
    }
}
