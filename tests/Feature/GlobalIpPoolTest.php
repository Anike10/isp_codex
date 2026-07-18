<?php

namespace Tests\Feature;

use App\Models\AppIpPool;
use App\Models\MikrotikRouter;
use App\Models\Permission;
use App\Models\User;
use App\Services\MikrotikImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalIpPoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_pool_name_is_shown_once_with_every_linked_router(): void
    {
        [$user, $firstRouter, $secondRouter] = $this->operatorAndRouters();
        $this->pool($firstRouter, 'shared-pool', '10.0.0.2-10.0.0.100');
        $this->pool($secondRouter, 'shared-pool', '10.0.0.2-10.0.0.100');

        $response = $this->actingAs($user)->get(route('ip-pools.index'));

        $response->assertOk()
            ->assertSee('data-pool-name="shared-pool"', false)
            ->assertSee($firstRouter->name)
            ->assertSee($secondRouter->name)
            ->assertSee('সব MikroTik-এও পরিবর্তন করুন');
        $this->assertSame(1, substr_count($response->getContent(), 'data-pool-name="shared-pool"'));
    }

    public function test_app_only_rename_creates_a_copy_for_every_router_and_keeps_old_pool(): void
    {
        [$user, $firstRouter, $secondRouter] = $this->operatorAndRouters();
        $representative = $this->pool($firstRouter, 'old-pool', '10.0.0.2-10.0.0.100');
        $this->pool($secondRouter, 'old-pool', '10.0.0.2-10.0.0.100');

        $this->actingAs($user)->patch(route('ip-pools.update', $representative), [
            'name' => 'new-pool',
            'ranges' => '10.1.0.2-10.1.0.100',
        ])->assertRedirect(route('ip-pools.index'));

        $this->assertSame(2, AppIpPool::where('name', 'old-pool')->count());
        $this->assertSame(2, AppIpPool::where('name', 'new-pool')->count());
        $this->assertSame(2, AppIpPool::where('name', 'new-pool')->where('ranges', '10.1.0.2-10.1.0.100')->count());
    }

    public function test_checked_update_preflights_and_updates_every_router_then_app_records(): void
    {
        [$user, $firstRouter, $secondRouter] = $this->operatorAndRouters();
        $representative = $this->pool($firstRouter, 'old-pool', '10.0.0.2-10.0.0.100');
        $this->pool($secondRouter, 'old-pool', '10.0.0.2-10.0.0.100');

        $service = $this->mock(MikrotikImportService::class);
        $service->shouldReceive('liveRecords')
            ->twice()
            ->withArgs(fn (MikrotikRouter $router, string $command) =>
                in_array($router->id, [$firstRouter->id, $secondRouter->id], true) && $command === '/ip/pool/print')
            ->andReturnUsing(fn (MikrotikRouter $router) => [[
                '.id' => '*'.$router->id,
                'name' => 'old-pool',
                'ranges' => '10.0.0.2-10.0.0.100',
            ]]);
        $service->shouldReceive('write')
            ->twice()
            ->withArgs(fn (MikrotikRouter $router, string $command, array $attributes) =>
                in_array($router->id, [$firstRouter->id, $secondRouter->id], true)
                && $command === '/ip/pool/set'
                && $attributes['.id'] === '*'.$router->id
                && $attributes['name'] === 'new-pool'
                && $attributes['ranges'] === '10.2.0.2-10.2.0.100')
            ->andReturn([]);

        $this->actingAs($user)->patch(route('ip-pools.update', $representative), [
            'name' => 'new-pool',
            'ranges' => '10.2.0.2-10.2.0.100',
            'sync_to_routers' => '1',
        ])->assertRedirect(route('ip-pools.index'));

        $this->assertDatabaseMissing('app_ip_pools', ['name' => 'old-pool']);
        $this->assertSame(2, AppIpPool::where('name', 'new-pool')->where('ranges', '10.2.0.2-10.2.0.100')->count());
    }

    public function test_app_only_delete_removes_group_without_touching_router(): void
    {
        [$user, $firstRouter, $secondRouter] = $this->operatorAndRouters();
        $representative = $this->pool($firstRouter, 'old-pool', '10.0.0.2-10.0.0.100');
        $this->pool($secondRouter, 'old-pool', '10.0.0.2-10.0.0.100');

        $this->actingAs($user)
            ->delete(route('ip-pools.destroy', $representative))
            ->assertRedirect(route('ip-pools.index'));

        $this->assertDatabaseMissing('app_ip_pools', ['name' => 'old-pool']);
    }

    private function operatorAndRouters(): array
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());

        return [$user, $this->router('Main Router', '10.0.0.1'), $this->router('Backup Router', '10.0.0.2')];
    }

    private function router(string $name, string $ip): MikrotikRouter
    {
        return MikrotikRouter::create([
            'name' => $name,
            'ip_address' => $ip,
            'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 10,
            'inactive_pppoe_profile' => 'inactive',
            'username' => 'api',
            'password' => 'secret',
            'status' => 'active',
        ]);
    }

    private function pool(MikrotikRouter $router, string $name, string $ranges): AppIpPool
    {
        return AppIpPool::create([
            'mikrotik_router_id' => $router->id,
            'name' => $name,
            'ranges' => $ranges,
            'status' => 'active',
        ]);
    }
}
