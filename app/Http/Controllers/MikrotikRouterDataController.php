<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\AppIpPool;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedIpPool;
use App\Models\MikrotikImportedProfile;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Services\MikrotikCustomerSyncService;
use App\Services\MikrotikImportService;
use Illuminate\Http\Request;
use Throwable;

class MikrotikRouterDataController extends Controller
{
    public function globalPools()
    {
        return view('mikrotik_routers.global_pools', [
            'pools' => AppIpPool::query()->with('router')->orderBy('name')->paginate(100),
        ]);
    }

    public function profiles(MikrotikRouter $mikrotikRouter)
    {
        return view('mikrotik_routers.profiles', [
            'mikrotikRouter' => $mikrotikRouter,
            'profiles' => $mikrotikRouter->importedProfiles()->orderBy('name')->paginate(100),
        ]);
    }

    public function pools(MikrotikRouter $mikrotikRouter)
    {
        return view('mikrotik_routers.pools', [
            'mikrotikRouter' => $mikrotikRouter,
            'pools' => AppIpPool::query()->where('mikrotik_router_id', $mikrotikRouter->id)->orderBy('name')->paginate(100),
            'mikrotikPools' => $mikrotikRouter->importedIpPools()->orderBy('name')->get(),
        ]);
    }

    public function createPool(Request $request, MikrotikRouter $mikrotikRouter)
    {
        $data = $this->poolData($request);
        $pool = AppIpPool::create([
            ...$data,
            'mikrotik_router_id' => $mikrotikRouter->id,
        ]);

        return redirect()->route('mikrotik-routers.pools.index', $mikrotikRouter)->with('success', "IP pool {$pool->name} added locally. Use Export to MikroTik to create it on the router.");
    }

    public function updatePool(Request $request, MikrotikRouter $mikrotikRouter, AppIpPool $appIpPool)
    {
        abort_unless($appIpPool->mikrotik_router_id === $mikrotikRouter->id, 404);
        $appIpPool->update($this->poolData($request));

        return back()->with('success', 'IP pool updated locally.');
    }

    public function deletePool(MikrotikRouter $mikrotikRouter, AppIpPool $appIpPool)
    {
        abort_unless($appIpPool->mikrotik_router_id === $mikrotikRouter->id, 404);
        $appIpPool->delete();

        return back()->with('success', 'Local IP pool entry deleted. Router was not changed.');
    }

    public function exportPool(MikrotikRouter $mikrotikRouter, AppIpPool $appIpPool, MikrotikImportService $service)
    {
        abort_unless($appIpPool->mikrotik_router_id === $mikrotikRouter->id, 404);
        $live = collect($service->liveRecords($mikrotikRouter, '/ip/pool/print'))->firstWhere('name', $appIpPool->name);
        $attributes = array_filter(['name' => $appIpPool->name, 'ranges' => $appIpPool->ranges, 'next-pool' => $appIpPool->next_pool, 'comment' => $appIpPool->notes], fn ($value) => $value !== null && $value !== '');
        if ($live) {
            $service->write($mikrotikRouter, '/ip/pool/set', ['.id' => $live['.id'], ...$attributes]);
        } else {
            $service->write($mikrotikRouter, '/ip/pool/add', $attributes);
        }

        app(MikrotikImportService::class)->importIpPools($mikrotikRouter);
        return back()->with('success', 'IP pool exported to MikroTik and re-imported for comparison.');
    }

    public function saveImportedPool(MikrotikRouter $mikrotikRouter, MikrotikImportedIpPool $mikrotikImportedIpPool)
    {
        abort_unless($mikrotikImportedIpPool->mikrotik_router_id === $mikrotikRouter->id, 404);
        AppIpPool::updateOrCreate(
            ['mikrotik_router_id' => $mikrotikRouter->id, 'name' => $mikrotikImportedIpPool->name],
            ['ranges' => $mikrotikImportedIpPool->ranges ?: '', 'next_pool' => $mikrotikImportedIpPool->next_pool, 'notes' => $mikrotikImportedIpPool->source_note, 'status' => 'active']
        );

        return back()->with('success', 'MikroTik pool saved to the app IP pool list.');
    }

    public function importLivePool(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $routerosId = $request->validate(['routeros_id' => ['required', 'string']])['routeros_id'];
        $pool = collect($service->liveRecords($mikrotikRouter, '/ip/pool/print'))
            ->first(fn (array $record) => ($record['.id'] ?? null) === $routerosId);
        abort_unless($pool && ! blank($pool['name'] ?? null), 404);

        AppIpPool::updateOrCreate(
            ['mikrotik_router_id' => $mikrotikRouter->id, 'name' => $pool['name']],
            ['ranges' => $pool['ranges'] ?? '', 'next_pool' => $pool['next-pool'] ?? null, 'notes' => $pool['comment'] ?? null, 'status' => 'active']
        );

        return redirect()->to(route('mikrotik-routers.compare', $mikrotikRouter).'#ip-pools')
            ->with('success', "IP pool {$pool['name']} saved to App IP Pools.");
    }

    public function importLiveSecretAsParty(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $routerosId = $request->validate(['routeros_id' => ['required', 'string']])['routeros_id'];
        $service->importSecrets($mikrotikRouter);
        $secret = $mikrotikRouter->importedSecrets()->where('routeros_id', $routerosId)->firstOrFail();

        $request->merge(['secret_ids' => [$secret->id], 'update_existing' => 1]);

        return app(MikrotikImportController::class)->createParties($request, $mikrotikRouter);
    }

    public function inlineUpdate(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $data = $request->validate([
            'type' => ['required', 'in:profile,pool,secret'],
            'field' => ['required', 'in:name,ranges'],
            'value' => ['required', 'string', 'max:1000'],
            'app_id' => ['nullable', 'integer'],
            'routeros_id' => ['nullable', 'string'],
        ]);
        abort_if(! $data['app_id'] && ! $data['routeros_id'], 422, 'No App or MikroTik record selected.');

        if ($data['type'] === 'profile') {
            abort_unless($data['field'] === 'name', 422);
            if ($data['app_id']) InternetPackage::findOrFail($data['app_id'])->update(['mikrotik_profile' => $data['value']]);
            if ($data['routeros_id']) $service->write($mikrotikRouter, '/ppp/profile/set', ['.id' => $data['routeros_id'], 'name' => $data['value']]);
            $service->importProfiles($mikrotikRouter);
        } elseif ($data['type'] === 'pool') {
            $pool = $data['app_id'] ? AppIpPool::whereKey($data['app_id'])->where('mikrotik_router_id', $mikrotikRouter->id)->firstOrFail() : null;
            if ($pool) $pool->update([$data['field'] === 'name' ? 'name' : 'ranges' => $data['value']]);
            if ($data['routeros_id']) $service->write($mikrotikRouter, '/ip/pool/set', ['.id' => $data['routeros_id'], $data['field'] => $data['value']]);
            $service->importIpPools($mikrotikRouter);
        } else {
            abort_unless($data['field'] === 'name', 422);
            if ($data['app_id']) {
                $customer = Customer::whereKey($data['app_id'])->where('mikrotik_router_id', $mikrotikRouter->id)->firstOrFail();
                $customer->update(['connection_id' => $data['value'], 'mikrotik_username' => $data['value']]);
            }
            if ($data['routeros_id']) $service->write($mikrotikRouter, '/ppp/secret/set', ['.id' => $data['routeros_id'], 'name' => $data['value']]);
            $service->importSecrets($mikrotikRouter);
        }

        return response()->json(['message' => 'Updated in the available App/MikroTik target(s).']);
    }

    public function compare(MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $liveDataAvailable = true;
        $liveDataError = null;

        try {
            $liveProfiles = collect($service->liveRecords($mikrotikRouter, '/ppp/profile/print'));
            $livePools = collect($service->liveRecords($mikrotikRouter, '/ip/pool/print'));
            $liveSecrets = collect($service->liveRecords($mikrotikRouter, '/ppp/secret/print'));
        } catch (Throwable $exception) {
            report($exception);

            $liveDataAvailable = false;
            $liveDataError = 'MikroTik live data is unavailable. Check the router connection and API credentials, then reload this page.';
            $liveProfiles = collect();
            $livePools = collect();
            $liveSecrets = collect();
        }
        $packageProfiles = InternetPackage::query()->whereNotNull('mikrotik_profile')->orderBy('mikrotik_profile')->get();
        // A Party without a MikroTik Target is still an App-side PPPoE user.
        // Include it here so an operator can choose this router by exporting it.
        // Do not include Parties explicitly assigned to another router.
        $customers = Customer::query()
            ->whereNotNull('connection_id')
            ->where('connection_id', '!=', '')
            ->where(function ($query) use ($mikrotikRouter): void {
                $query->where('mikrotik_router_id', $mikrotikRouter->id)
                    ->orWhereNull('mikrotik_router_id');
            })
            ->orderBy('connection_id')
            ->get();
        $localPools = AppIpPool::query()->where('mikrotik_router_id', $mikrotikRouter->id)->orderBy('name')->get();

        return view('mikrotik_routers.compare', compact('mikrotikRouter', 'liveProfiles', 'livePools', 'liveSecrets', 'packageProfiles', 'customers', 'localPools', 'liveDataAvailable', 'liveDataError'));
    }

    public function exportProfile(MikrotikRouter $mikrotikRouter, InternetPackage $package, MikrotikImportService $service)
    {
        $name = $package->mikrotik_profile ?: $package->name;
        $live = collect($service->liveRecords($mikrotikRouter, '/ppp/profile/print'))->firstWhere('name', $name);
        $attributes = ['name' => $name];
        if ($live) {
            $service->write($mikrotikRouter, '/ppp/profile/set', ['.id' => $live['.id'], ...$attributes]);
        } else {
            $service->write($mikrotikRouter, '/ppp/profile/add', $attributes);
        }
        $service->importProfiles($mikrotikRouter);
        return back()->with('success', "Profile {$name} exported to MikroTik.");
    }

    public function exportCustomer(MikrotikRouter $mikrotikRouter, Customer $customer, MikrotikCustomerSyncService $service)
    {
        abort_unless(in_array($customer->mikrotik_router_id, [null, $mikrotikRouter->id], true), 404);
        if ($customer->mikrotik_router_id === null) {
            $customer->update(['mikrotik_router_id' => $mikrotikRouter->id]);
        }
        $service->sync($customer);
        return back()->with('success', "PPPoE user {$customer->connection_id} exported to MikroTik.");
    }

    public function deleteRouterExtra(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $data = $request->validate(['type' => ['required', 'in:profile,pool,secret'], 'routeros_id' => ['required', 'string']]);
        $commands = ['profile' => '/ppp/profile/remove', 'pool' => '/ip/pool/remove', 'secret' => '/ppp/secret/remove'];
        $service->write($mikrotikRouter, $commands[$data['type']], ['.id' => $data['routeros_id']]);

        return back()->with('success', 'Selected extra item deleted from MikroTik.');
    }

    private function poolData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ranges' => ['required', 'string'],
            'next_pool' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }
}
