<?php

namespace App\Http\Controllers;

use App\Models\AppIpPool;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedProfile;
use Illuminate\Http\Request;

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
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
                ->when($request->filled('min_price'), fn ($query) => $query->where('monthly_price', '>=', (float) $request->query('min_price')))
                ->when($request->filled('max_price'), fn ($query) => $query->where('monthly_price', '<=', (float) $request->query('max_price')))
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
        ]);
    }

    public function create()
    {
        return view('packages.create', [
            'ipPoolNames' => $this->ipPoolNames(),
            'runningPoolProfiles' => collect(),
        ]);
    }

    public function edit(InternetPackage $package)
    {
        $profileName = $package->mikrotik_profile ?: $package->name;

        return view('packages.create', [
            'package' => $package,
            'ipPoolNames' => $this->ipPoolNames(),
            'runningPoolProfiles' => MikrotikImportedProfile::query()
                ->with('router')
                ->where('name', $profileName)
                ->orderBy('mikrotik_router_id')
                ->get(),
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
            return back()->with('error', 'This package is assigned to customers, so it cannot be deleted. Mark it inactive instead.');
        }

        $package->delete();

        return redirect()->route('packages.index')->with('success', 'Package deleted.');
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
}
