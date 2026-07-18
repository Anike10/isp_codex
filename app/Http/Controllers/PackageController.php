<?php

namespace App\Http\Controllers;

use App\Models\AppIpPool;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedProfile;
use App\Models\MikrotikRouter;
use App\Models\Subscription;
use App\Services\MikrotikImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        return view('packages.index', [
            'packages' => InternetPackage::query()
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->query('search'));
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('speed', 'like', "%{$search}%")
                            ->orWhere('mikrotik_profile', 'like', "%{$search}%")
                            ->orWhere('default_ip_pool', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
                ->when($request->filled('min_price'), fn ($query) => $query->where('monthly_price', '>=', (float) $request->query('min_price')))
                ->when($request->filled('max_price'), fn ($query) => $query->where('monthly_price', '<=', (float) $request->query('max_price')))
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
            'replacementPackages' => InternetPackage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('packages.create', [
            'ipPoolNames' => $this->ipPoolNames(),
            'runningPoolProfiles' => collect(),
        ]);
    }

    public function edit(InternetPackage $package, MikrotikImportService $service)
    {
        $profileName = $package->mikrotik_profile ?: $package->name;

        return view('packages.create', [
            'package' => $package,
            'ipPoolNames' => $this->ipPoolNames(),
            'runningPoolProfiles' => $this->runningPoolProfiles($profileName, $service),
        ]);
    }

    public function show(InternetPackage $package)
    {
        $package->loadCount('subscriptions');
        $versions = $package->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('packages.show', compact('package', 'versions'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePackage($request);
        $data['mikrotik_profile'] = $data['mikrotik_profile'] ?: $data['name'];

        InternetPackage::create($data);

        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }

    public function update(Request $request, InternetPackage $package)
    {
        $data = $this->validatePackage($request);
        $data['mikrotik_profile'] = $data['mikrotik_profile'] ?: $data['name'];

        $package->update($data);

        return redirect()->route('packages.show', $package)->with('success', 'Package updated successfully.');
    }

    public function inlineUpdate(Request $request, InternetPackage $package)
    {
        $field = $request->validate([
            'field' => ['required', 'in:name,speed,mikrotik_profile,monthly_price,status'],
            'value' => ['nullable'],
        ])['field'];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'speed' => ['required', 'string', 'max:100'],
            'mikrotik_profile' => ['nullable', 'string', 'max:255'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ];
        $value = validator(['value' => $request->input('value')], ['value' => $rules[$field]])->validate()['value'];

        $package->update([$field => $value]);
        $fresh = $package->fresh();

        return response()->json([
            'message' => 'Package updated.',
            'value' => $field === 'monthly_price' ? number_format((float) $fresh->monthly_price, 2) : $fresh->{$field},
            'status' => $fresh->status,
        ]);
    }

    public function destroy(InternetPackage $package)
    {
        if ($package->subscriptions()->exists()) {
            return back()->with('error', "Package '{$package->name}' is assigned to customers, so it cannot be deleted. Mark it inactive or use Force Delete with a replacement package.");
        }

        $package->delete();

        return redirect()->route('packages.index')->with('success', 'Package deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'package_ids' => ['required', 'array', 'min:1'],
            'package_ids.*' => ['integer', 'distinct', 'exists:internet_packages,id'],
            'force_delete' => ['nullable', 'boolean'],
            'replacement_package_id' => ['nullable', 'integer', 'exists:internet_packages,id'],
        ]);
        $packageIds = collect($data['package_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $packages = InternetPackage::query()->whereIn('id', $packageIds)->withCount('subscriptions')->orderBy('name')->get();

        if ($request->boolean('force_delete')) {
            $replacementId = (int) ($data['replacement_package_id'] ?? 0);
            if (! $replacementId) {
                return back()->withInput()->with('error', 'Force Delete requires a replacement package for affected users.');
            }
            if ($packageIds->contains($replacementId)) {
                return back()->withInput()->with('error', 'The replacement package cannot also be selected for deletion.');
            }

            $replacement = InternetPackage::findOrFail($replacementId);
            $subscriptionCount = Subscription::query()->whereIn('internet_package_id', $packageIds)->count();
            DB::transaction(function () use ($packageIds, $packages, $replacementId): void {
                Subscription::query()->whereIn('internet_package_id', $packageIds)->update([
                    'internet_package_id' => $replacementId,
                    'updated_at' => now(),
                ]);
                $packages->each->delete();
            });

            return redirect()->route('packages.index')->with('success', "Force deleted {$packages->count()} package(s). Moved {$subscriptionCount} subscription record(s) to '{$replacement->name}'. Scheduled MikroTik sync will apply the replacement profile.");
        }

        $assigned = $packages->where('subscriptions_count', '>', 0);
        $deletable = $packages->where('subscriptions_count', 0);
        DB::transaction(fn () => $deletable->each->delete());

        $response = redirect()->route('packages.index');
        if ($deletable->isNotEmpty()) {
            $response->with('success', 'Deleted packages: '.$deletable->pluck('name')->join(', ').'.');
        }
        if ($assigned->isNotEmpty()) {
            $response->with('error', 'Could not delete assigned packages: '.$assigned->pluck('name')->join(', ').'. Mark them inactive or use Force Delete with a replacement package.');
        }

        return $response;
    }

    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'speed' => ['required', 'string', 'max:100'],
            'mikrotik_profile' => ['nullable', 'string', 'max:255'],
            'default_ip_pool' => ['nullable', 'string', 'max:255', 'exists:app_ip_pools,name'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    private function ipPoolNames()
    {
        return AppIpPool::query()->select('name')->distinct()->orderBy('name')->pluck('name');
    }

    private function runningPoolProfiles(string $profileName, MikrotikImportService $service)
    {
        $snapshots = MikrotikImportedProfile::query()
            ->where('name', $profileName)
            ->get()
            ->keyBy('mikrotik_router_id');

        return MikrotikRouter::query()->orderBy('name')->get()->map(function (MikrotikRouter $router) use ($profileName, $service, $snapshots) {
            $snapshot = $snapshots->get($router->id);

            if ($router->status !== 'active') {
                return (object) [
                    'router' => $router,
                    'remote_address' => $snapshot?->remote_address,
                    'profile_found' => (bool) $snapshot,
                    'source_label' => $snapshot ? 'Last imported · router disabled' : 'Router disabled',
                    'checked_at' => $snapshot?->imported_at,
                ];
            }

            try {
                $live = collect($service->liveRecords($router, '/ppp/profile/print'))->firstWhere('name', $profileName);

                return (object) [
                    'router' => $router,
                    'remote_address' => $live['remote-address'] ?? null,
                    'profile_found' => (bool) $live,
                    'source_label' => 'Live RouterOS',
                    'checked_at' => now(),
                ];
            } catch (Throwable $exception) {
                report($exception);

                return (object) [
                    'router' => $router,
                    'remote_address' => $snapshot?->remote_address,
                    'profile_found' => (bool) $snapshot,
                    'source_label' => $snapshot ? 'Last imported · live unavailable' : 'Live unavailable',
                    'checked_at' => $snapshot?->imported_at,
                ];
            }
        });
    }
}
