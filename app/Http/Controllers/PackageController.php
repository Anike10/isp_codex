<?php

namespace App\Http\Controllers;

use App\Models\InternetPackage;
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
        return view('packages.create');
    }

    public function edit(InternetPackage $package)
    {
        return view('packages.create', compact('package'));
    }

    public function show(InternetPackage $package)
    {
        $package->load('versions')->loadCount('subscriptions');

        return view('packages.show', compact('package'));
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

    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'speed' => ['required', 'string', 'max:100'],
            'mikrotik_profile' => ['nullable', 'string', 'max:255'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
