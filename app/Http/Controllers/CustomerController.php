<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikRouter;
use App\Models\ResellerCommissionHistory;
use App\Models\Subscription;
use App\Observers\RecordVersionObserver;
use App\Services\MikrotikCustomerSyncService;
use App\Services\RecordVersionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hasImportedSecretTable = Schema::hasTable('mikrotik_imported_secrets');
        } catch (\Throwable) {
            // In deployments where the migration is not present (or DB privileges differ),
            // keep the page functional without importing-related features.
            $hasImportedSecretTable = false;
        }

        $customers = Customer::query()
            ->with('activeSubscription.package')
            ->withExists('subscriptions')
            ->withExists('invoices')
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
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->query('role') === 'customer', fn ($query) => $query->where('is_customer', true))
            ->when($request->query('role') === 'vendor', fn ($query) => $query->where('is_vendor', true))
            ->when($request->query('role') === 'reseller', fn ($query) => $query->where('is_reseller', true))
            ->when($request->filled('package_id'), fn ($query) => $query->whereHas('activeSubscription', fn ($query) => $query->where('internet_package_id', $request->integer('package_id'))))
            ->when($request->query('due_state') === 'due', fn ($query) => $query->whereHas('invoices', fn ($query) => $query->where('due_amount', '>', 0)))
            ->when($request->query('due_state') === 'advance', fn ($query) => $query->where('account_balance', '>', 0))
            ->when($hasImportedSecretTable, function ($query) {
                return $query->withExists('importedSecret');
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('customers.index', [
            'customers' => $customers,
            'hasImportedSecretTable' => $hasImportedSecretTable,
            'packages' => InternetPackage::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('customers.create', [
            'packages' => InternetPackage::where('status', 'active')->orderBy('name')->get(),
            'routers' => MikrotikRouter::where('status', 'active')->orderBy('name')->get(),
            'resellers' => Customer::where('is_reseller', true)->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'connection_id' => ['required_with:internet_package_id', 'required_if:use_fixed_ip,1', 'nullable', 'string', 'max:100', 'unique:customers,connection_id'],
            'address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'is_customer' => ['nullable', 'boolean'],
            'is_vendor' => ['nullable', 'boolean'],
            'is_reseller' => ['nullable', 'boolean'],
            'reseller_id' => ['nullable', Rule::exists('customers', 'id')->where('is_reseller', true)],
            'reseller_daily_payment_limit' => ['nullable', 'numeric', 'min:1'],
            'reseller_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'never_suspend' => ['nullable', 'boolean'],
            'mikrotik_router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'use_fixed_ip' => ['nullable', 'boolean'],
            'fixed_ip_address' => ['nullable', 'required_if:use_fixed_ip,1', 'ip', 'max:45', 'unique:customers,fixed_ip_address'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $this->normalizeCustomerConnectionData($data);
        $data['is_customer'] = (bool) ($data['is_customer'] ?? false) || ! empty($data['internet_package_id']);
        $data['is_vendor'] = (bool) ($data['is_vendor'] ?? false);
        $this->normalizeResellerData($data);
        $this->ensurePartyHasRole($data);
        $data['never_suspend'] = (bool) ($data['never_suspend'] ?? false);
        $this->normalizeIpMode($data);

        $customer = Customer::create($data);

        if ($customer->is_reseller) {
            ResellerCommissionHistory::create([
                'reseller_id' => $customer->id,
                'old_percent' => null,
                'new_percent' => $customer->reseller_commission_percent,
                'changed_by' => $request->user()?->id,
                'changed_at' => now(),
                'note' => 'Initial reseller commission.',
            ]);
        }

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
            'resellers' => Customer::where('is_reseller', true)->whereKeyNot($customer->id)->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer, RecordVersionService $recordVersionService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'connection_id' => ['required_with:internet_package_id', 'required_if:use_fixed_ip,1', 'nullable', 'string', 'max:100', Rule::unique('customers', 'connection_id')->ignore($customer->id)],
            'address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'is_customer' => ['nullable', 'boolean'],
            'is_vendor' => ['nullable', 'boolean'],
            'is_reseller' => ['nullable', 'boolean'],
            'reseller_id' => ['nullable', Rule::exists('customers', 'id')->where('is_reseller', true), Rule::notIn([$customer->id])],
            'reseller_daily_payment_limit' => ['nullable', 'numeric', 'min:1'],
            'reseller_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'never_suspend' => ['nullable', 'boolean'],
            'mikrotik_router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'use_fixed_ip' => ['nullable', 'boolean'],
            'fixed_ip_address' => ['nullable', 'required_if:use_fixed_ip,1', 'ip', 'max:45', Rule::unique('customers', 'fixed_ip_address')->ignore($customer->id)],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $this->normalizeCustomerConnectionData($data, $customer);
        $data['is_customer'] = (bool) ($data['is_customer'] ?? false) || ! empty($data['internet_package_id']);
        $data['is_vendor'] = (bool) ($data['is_vendor'] ?? false);
        $this->normalizeResellerData($data, $customer);
        $this->ensurePartyHasRole($data);
        $data['never_suspend'] = (bool) ($data['never_suspend'] ?? false);
        $this->normalizeIpMode($data);

        DB::transaction(function () use (&$customer, $data, $recordVersionService): void {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $oldCommissionPercent = (float) $customer->reseller_commission_percent;
            $activeSubscription = $customer->activeSubscription()->with('package')->lockForUpdate()->first();
            $customer->setRelation('activeSubscription', $activeSubscription);
            $oldSnapshot = $recordVersionService->snapshot($customer, ['activeSubscription.package']);

            $newPackageId = ! empty($data['internet_package_id']) ? (int) $data['internet_package_id'] : null;
            $oldPackageId = $activeSubscription?->internet_package_id ? (int) $activeSubscription->internet_package_id : null;
            $connectionChanged = $customer->connection_id !== ($data['connection_id'] ?? null)
                || (int) ($customer->mikrotik_router_id ?? 0) !== (int) ($data['mikrotik_router_id'] ?? 0);
            $switchedFromFixedToDynamic = $customer->use_fixed_ip && ! $data['use_fixed_ip'];
            $customerData = Arr::except($data, ['internet_package_id', 'start_date']);

            if ($oldPackageId !== $newPackageId || $connectionChanged || $switchedFromFixedToDynamic) {
                $customerData['learned_ip_address'] = null;
                $customerData['learned_ip_package_id'] = null;
            }

            RecordVersionObserver::withoutRecording(fn () => $customer->update($customerData));

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

            $newSnapshot = $recordVersionService->snapshot($customer->refresh(), ['activeSubscription.package']);
            $recordVersionService->recordUpdate($customer, $oldSnapshot, $newSnapshot, [
                'source' => 'party_edit',
                'party_name' => $customer->name,
            ]);

            $newCommissionPercent = (float) $customer->reseller_commission_percent;
            if ($customer->is_reseller && $oldCommissionPercent !== $newCommissionPercent) {
                ResellerCommissionHistory::create([
                    'reseller_id' => $customer->id,
                    'old_percent' => $oldCommissionPercent,
                    'new_percent' => $newCommissionPercent,
                    'changed_by' => auth()->id(),
                    'changed_at' => now(),
                    'note' => 'Commission changed from party edit.',
                ]);
            }
        });

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
            'reseller',
            'resellerCustomers',
            'loginUsers.roles',
            'reseller.commissionHistories.changedByUser',
            'commissionHistories.changedByUser',
            'invoices' => fn ($query) => $query->latest(),
            'balanceTransactions' => fn ($query) => $query->latest()->limit(10),
            'tickets' => fn ($query) => $query->latest(),
            'productSerials' => fn ($query) => $query->with(['product', 'invoice', 'warrantyClaims'])->latest('sold_at')->limit(50),
            'warrantyClaims' => fn ($query) => $query->with(['product', 'productSerial'])->latest()->limit(10),
        ]);
        $versions = $customer->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('customers.show', compact('customer', 'versions'));
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

    public function activateUntilNextDate(Request $request, Customer $customer)
    {
        $subscription = $customer->activeSubscription ?: $customer->subscriptions()->with('package')->latest()->first();
        $data = $request->validate([
            'active_until' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        if (! $subscription || ! $subscription->package) {
            return back()->withErrors(['active_until' => 'No package found for this customer to activate.']);
        }

        $nextDate = $data['active_until'] ?? now()->addMonthNoOverflow()->toDateString();
        $detail = sprintf(
            '[%s] Activated package to %s via quick-activate action.',
            now()->format('Y-m-d H:i'),
            $nextDate
        );

        DB::transaction(function () use ($customer, $subscription, $nextDate, $detail): void {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $subscription = Subscription::query()->whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            $customer->update([
                'status' => 'active',
                'service_valid_from' => now()->toDateString(),
                'service_valid_until' => $nextDate,
                'service_validity_note' => $detail,
                'notes' => trim(implode("\n", array_filter([$customer->notes, $detail]))),
                'grace_until' => null,
                'grace_days' => null,
                'grace_used_at' => null,
            ]);

            $subscription->update([
                'status' => 'active',
                'end_date' => null,
            ]);
        });

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());

        return back()
            ->with('success', "Package has been activated until {$nextDate}. MikroTik user {$syncResult['status']}.")
            ->with('warning', $syncResult['warning']);
    }

    public function updateServiceValidity(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'service_valid_until' => ['required', 'date'],
            'validity_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $previous = $customer->service_valid_until?->format('Y-m-d') ?? 'not set';
        $newUntil = $data['service_valid_until'];
        $detail = sprintf(
            '[%s] Manual validity override: %s → %s. Reason: %s',
            now()->format('Y-m-d H:i'),
            $previous,
            $newUntil,
            trim($data['validity_note'])
        );

        $expiresToday = Carbon::parse($newUntil)->lt(today());
        $subscription = $customer->activeSubscription ?: $customer->subscriptions()->latest()->first();

        $customer->update([
            'service_valid_until' => $newUntil,
            'service_validity_note' => $detail,
            'notes' => trim(implode("\n", array_filter([$customer->notes, $detail]))),
            'status' => $expiresToday ? 'inactive' : 'active',
        ]);

        if ($subscription) {
            $subscription->update([
                'status' => $expiresToday ? 'inactive' : 'active',
                'end_date' => $expiresToday ? $newUntil : null,
            ]);
        }

        $syncResult = $this->syncMikrotikCustomer($customer);
        $state = $expiresToday ? 'expired and moved to the inactive profile' : 'made active and synced to the service profile';

        return back()
            ->with('success', "Validity date updated. Party was {$state}; MikroTik user {$syncResult['status']}.")
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

    private function normalizeIpMode(array &$data): void
    {
        $data['use_fixed_ip'] = (bool) ($data['use_fixed_ip'] ?? false);
        $data['fixed_ip_address'] = $data['use_fixed_ip']
            ? trim((string) ($data['fixed_ip_address'] ?? ''))
            : null;
    }

    private function ensurePartyHasRole(array $data): void
    {
        if (! $data['is_customer'] && ! $data['is_vendor'] && ! $data['is_reseller']) {
            throw ValidationException::withMessages([
                'is_customer' => 'Select at least Customer, Vendor, or Reseller for this party.',
            ]);
        }
    }

    private function normalizeResellerData(array &$data, ?Customer $customer = null): void
    {
        $data['is_reseller'] = (bool) ($data['is_reseller'] ?? false);

        if ($data['is_reseller']) {
            $data['reseller_id'] = null;
            $data['reseller_daily_payment_limit'] = filled($data['reseller_daily_payment_limit'] ?? null)
                ? round((float) $data['reseller_daily_payment_limit'], 2)
                : null;
            $data['reseller_commission_percent'] = round((float) ($data['reseller_commission_percent'] ?? 0), 2);
        } else {
            // Keep reseller settings when the role is temporarily disabled so
            // re-enabling the role does not silently erase the agreed terms.
            $data['reseller_daily_payment_limit'] = $customer?->reseller_daily_payment_limit;
            $data['reseller_commission_percent'] = $customer?->reseller_commission_percent ?? 0;
            $data['reseller_id'] = filled($data['reseller_id'] ?? null) ? (int) $data['reseller_id'] : null;
        }
    }
}
