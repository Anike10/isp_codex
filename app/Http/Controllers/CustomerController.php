<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use App\Models\Subscription;
use App\Services\MikrotikCustomerSyncService;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Throwable;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->with('activeSubscription.package')
            ->withExists('subscriptions')
            ->withSum('invoices as total_due_amount', 'due_amount')
            ->withMax(['invoices as latest_paid_billing_month' => function ($query) {
                $query->where('invoice_type', 'service')
                    ->where('due_amount', '<=', 0);
            }], 'billing_month')
            ->withMin(['invoices as earliest_unpaid_billing_month' => function ($query) {
                $query->where('invoice_type', 'service')
                    ->where('due_amount', '>', 0);
            }], 'billing_month')
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('connection_id', 'like', "%{$search}%")
                        ->orWhere('mikrotik_username', 'like', "%{$search}%");
                });
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
            'connection_id' => ['required_with:internet_package_id', 'nullable', 'string', 'max:100', 'unique:customers,connection_id'],
            'address' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'is_customer' => ['nullable', 'boolean'],
            'is_vendor' => ['nullable', 'boolean'],
            'never_suspend' => ['nullable', 'boolean'],
            'mikrotik_router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $this->normalizeCustomerConnectionData($data);
        $data['is_customer'] = (bool) ($data['is_customer'] ?? false) || ! empty($data['internet_package_id']);
        $data['is_vendor'] = (bool) ($data['is_vendor'] ?? false);
        $this->ensurePartyHasRole($data);
        $data['never_suspend'] = (bool) ($data['never_suspend'] ?? false);

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
            ->with('success', 'Party created successfully. MikroTik user '.$syncResult['status'].'.')
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
            'connection_id' => ['required_with:internet_package_id', 'nullable', 'string', 'max:100', Rule::unique('customers', 'connection_id')->ignore($customer->id)],
            'address' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'is_customer' => ['nullable', 'boolean'],
            'is_vendor' => ['nullable', 'boolean'],
            'never_suspend' => ['nullable', 'boolean'],
            'mikrotik_router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $this->normalizeCustomerConnectionData($data, $customer);
        $data['is_customer'] = (bool) ($data['is_customer'] ?? false) || ! empty($data['internet_package_id']);
        $data['is_vendor'] = (bool) ($data['is_vendor'] ?? false);
        $this->ensurePartyHasRole($data);
        $data['never_suspend'] = (bool) ($data['never_suspend'] ?? false);

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
            ->with('success', 'Party updated successfully. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'activeSubscription.package',
            'subscriptions.package',
            'mikrotikRouter',
            'invoices' => fn ($query) => $query->latest(),
            'balanceTransactions' => fn ($query) => $query->latest()->limit(10),
            'tickets' => fn ($query) => $query->latest(),
            'productSerials' => fn ($query) => $query->with(['product', 'invoice', 'warrantyClaims'])->latest('sold_at')->limit(50),
            'warrantyClaims' => fn ($query) => $query->with(['product', 'productSerial'])->latest()->limit(10),
        ]);

        return view('customers.show', compact('customer'));
    }

    public function grantGracePeriod(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'grace_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        if ($customer->grace_used_at) {
            return back()->withErrors(['grace_days' => 'Grace period was already used for this customer.']);
        }

        if ($customer->status === 'active' && ($customer->activeDaysRemaining() ?? 0) >= 0) {
            return back()->withErrors(['grace_days' => 'Grace period can only be given after the paid period expires.']);
        }

        $subscription = $customer->activeSubscription ?: $customer->subscriptions()->with('package')->latest()->first();

        if (! $subscription || ! $subscription->package) {
            return back()->withErrors(['grace_days' => 'Grace period requires an existing package/subscription for this customer.']);
        }

        $customer->update([
            'status' => 'active',
            'grace_days' => $data['grace_days'],
            'grace_until' => now()->addDays((int) $data['grace_days'])->toDateString(),
            'grace_used_at' => now(),
        ]);

        $subscription->update([
            'status' => 'active',
            'end_date' => null,
        ]);

        $syncResult = $this->syncMikrotikCustomer($customer);

        return back()
            ->with('success', 'Grace period added. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    private function syncMikrotikCustomer(Customer $customer): array
    {
        if (! $customer->mikrotik_username && ! $customer->connection_id) {
            return [
                'status' => 'skipped (no connection ID)',
                'warning' => null,
            ];
        }

        try {
            $status = app(MikrotikCustomerSyncService::class)->sync($customer->refresh());

            return [
                'status' => $status,
                'warning' => str_contains($status, 'failed -') ? 'Some MikroTik routers failed. '.$status : null,
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'not synced',
                'warning' => 'MikroTik sync failed: '.$exception->getMessage(),
            ];
        }
    }

    private function normalizeCustomerConnectionData(array &$data, ?Customer $customer = null): void
    {
        $connectionId = trim((string) ($data['connection_id'] ?? '')) ?: null;

        $data['connection_id'] = $connectionId;
        $data['mikrotik_username'] = $connectionId;
        $data['mikrotik_password'] = $connectionId
            ? ($customer?->mikrotik_password ?: MikrotikCustomerSyncService::DEFAULT_PASSWORD)
            : null;

        if (! $connectionId) {
            $data['mikrotik_router_id'] = null;
        }
    }

    private function ensurePartyHasRole(array $data): void
    {
        if (! $data['is_customer'] && ! $data['is_vendor']) {
            throw ValidationException::withMessages([
                'is_customer' => 'Select at least Customer or Vendor for this party.',
            ]);
        }
    }
}
