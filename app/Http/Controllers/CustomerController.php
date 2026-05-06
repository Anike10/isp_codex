<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use App\Models\Subscription;
use App\Services\MikrotikCustomerSyncService;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Throwable;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->with('activeSubscription.package')
            ->when($request->search, function ($query, string $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('connection_id', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create', [
            'packages' => InternetPackage::where('status', 'active')->orderBy('name')->get(),
            'routers' => MikrotikRouter::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'connection_id' => ['required', 'string', 'max:100', 'unique:customers,connection_id'],
            'address' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'mikrotik_router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $data['mikrotik_username'] = $data['connection_id'];
        $data['mikrotik_password'] = MikrotikCustomerSyncService::DEFAULT_PASSWORD;

        $customer = Customer::create($data);

        if (! empty($data['internet_package_id'])) {
            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $data['internet_package_id'],
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $syncResult = $this->syncMikrotikCustomer($customer);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer created successfully. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    public function edit(Customer $customer)
    {
        $customer->load('activeSubscription');

        return view('customers.edit', [
            'customer' => $customer,
            'packages' => InternetPackage::where('status', 'active')->orderBy('name')->get(),
            'routers' => MikrotikRouter::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'connection_id' => ['required', 'string', 'max:100', 'unique:customers,connection_id,'.$customer->id],
            'address' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'mikrotik_router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $data['mikrotik_username'] = $data['connection_id'];
        $data['mikrotik_password'] = $customer->mikrotik_password ?: MikrotikCustomerSyncService::DEFAULT_PASSWORD;

        $customer->update(Arr::except($data, ['internet_package_id', 'start_date']));

        $activeSubscription = $customer->activeSubscription;

        if (! empty($data['internet_package_id'])) {
            if ($activeSubscription) {
                $activeSubscription->update([
                    'internet_package_id' => $data['internet_package_id'],
                    'start_date' => $data['start_date'] ?? $activeSubscription->start_date ?? now()->toDateString(),
                    'end_date' => null,
                ]);
            } else {
                Subscription::create([
                    'customer_id' => $customer->id,
                    'internet_package_id' => $data['internet_package_id'],
                    'start_date' => $data['start_date'] ?? now()->toDateString(),
                    'status' => 'active',
                ]);
            }
        } elseif ($activeSubscription) {
            $activeSubscription->update([
                'status' => 'inactive',
                'end_date' => now()->toDateString(),
            ]);
        }

        $syncResult = $this->syncMikrotikCustomer($customer);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    public function show(Customer $customer)
    {
        $customer->load(['activeSubscription.package', 'mikrotikRouter', 'invoices' => fn ($query) => $query->latest(), 'tickets' => fn ($query) => $query->latest()]);

        return view('customers.show', compact('customer'));
    }

    private function syncMikrotikCustomer(Customer $customer): array
    {
        try {
            $status = app(MikrotikCustomerSyncService::class)->sync($customer->refresh());

            return ['status' => $status, 'warning' => null];
        } catch (Throwable $exception) {
            return [
                'status' => 'not synced',
                'warning' => 'MikroTik sync failed: '.$exception->getMessage(),
            ];
        }
    }
}
