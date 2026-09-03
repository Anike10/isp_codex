<?php

namespace Tests\Feature;

use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\PppUsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataUsageSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'view_network_diagnostics')->firstOrFail());

        return $user;
    }

    private function router(string $name): MikrotikRouter
    {
        return MikrotikRouter::create([
            'name' => $name, 'ip_address' => fake()->unique()->ipv4(), 'api_port' => 8181,
            'transport' => 'rest', 'username' => 'reader', 'password' => 'secret',
            'status' => 'active', 'read_only' => false,
        ]);
    }

    private function log(string $username, ?MikrotikRouter $router, string $disconnectedAt, int $down, int $up = 0): void
    {
        PppUsageLog::create([
            'mikrotik_router_id' => $router?->id,
            'username' => $username,
            'download_bytes' => $down,
            'upload_bytes' => $up,
            'disconnected_at' => $disconnectedAt,
        ]);
    }

    public function test_summary_shows_a_sortable_mikrotik_column_using_the_latest_session_router(): void
    {
        $alpha = $this->router('Alpha Router');
        $bravo = $this->router('Bravo Router');

        $this->log('pppoe-a', $alpha, now()->subDays(2)->toDateTimeString(), 100);
        $this->log('pppoe-b', $bravo, now()->subDay()->toDateTimeString(), 500);
        // Split across routers: newest session is on Bravo, so Bravo wins.
        $this->log('pppoe-c', $alpha, now()->subDays(5)->toDateTimeString(), 10);
        $this->log('pppoe-c', $bravo, now()->subHours(3)->toDateTimeString(), 20);

        $response = $this->actingAs($this->admin())
            ->get(route('troubleshoot.data-usage', ['sort' => 'router_name', 'dir' => 'asc']))
            ->assertOk()
            ->assertSee('MikroTik')
            ->assertSee('Alpha Router')
            ->assertSee('Bravo Router');

        // Ascending by router name: Alpha's user before Bravo's users.
        $response->assertSeeInOrder(['Alpha Router', 'Bravo Router']);

        $rows = $response->viewData('rows');
        $byUser = $rows->getCollection()->keyBy('username');
        $this->assertSame('Alpha Router', $byUser['pppoe-a']->router_name);
        $this->assertSame('Bravo Router', $byUser['pppoe-c']->router_name);
    }

    public function test_summary_can_filter_by_mikrotik_router(): void
    {
        $alpha = $this->router('Alpha Router');
        $bravo = $this->router('Bravo Router');

        $this->log('pppoe-a', $alpha, now()->subDay()->toDateTimeString(), 100);
        $this->log('pppoe-b', $bravo, now()->subDay()->toDateTimeString(), 500);

        $rows = $this->actingAs($this->admin())
            ->get(route('troubleshoot.data-usage', ['router_id' => $alpha->id]))
            ->assertOk()
            ->assertSee('pppoe-a')
            ->assertDontSee('pppoe-b')
            ->viewData('rows');

        $this->assertCount(1, $rows->getCollection());
    }
}
