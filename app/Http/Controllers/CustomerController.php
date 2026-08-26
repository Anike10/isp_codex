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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $hasImportedSecretTable = $this->hasImportedSecretTable();
        $perPage = $this->perPage($request, 200);
        $today = now()->toDateString();
        $expiryCountQuery = Customer::query()->whereNotNull('service_valid_until');
        $expirySummary = [
            'expired_last_7' => (clone $expiryCountQuery)
                ->whereDate('service_valid_until', '>=', now()->subDays(7)->toDateString())
                ->whereDate('service_valid_until', '<', $today)
                ->count(),
            'today' => (clone $expiryCountQuery)
                ->whereDate('service_valid_until', $today)
                ->count(),
            'tomorrow' => (clone $expiryCountQuery)
                ->whereDate('service_valid_until', '>=', $today)
                ->whereDate('service_valid_until', '<=', now()->addDay()->toDateString())
                ->count(),
            'in_7_days' => (clone $expiryCountQuery)
                ->whereDate('service_valid_until', '>=', $today)
                ->whereDate('service_valid_until', '<=', now()->addDays(7)->toDateString())
                ->count(),
        ];
        $customers = $this->customerQueryForIndex($request, $hasImportedSecretTable, false)
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return view('customers.index', [
            'customers' => $customers,
            'hasImportedSecretTable' => $hasImportedSecretTable,
            'packages' => InternetPackage::where('status', 'active')->orderBy('name')->get(),
            'expirySummary' => $expirySummary,
            'showDeletedCustomers' => false,
            'perPage' => $perPage,
            'perPageDefault' => 200,
        ]);
    }

    public function deleted(Request $request)
    {
        $hasImportedSecretTable = $this->hasImportedSecretTable();
        $perPage = $this->perPage($request, 200);
        $customers = $this->customerQueryForIndex($request, $hasImportedSecretTable, true)
            ->latest('deleted_at')
            ->paginate($perPage)
            ->appends($request->query());

        return view('customers.index', [
            'customers' => $customers,
            'hasImportedSecretTable' => $hasImportedSecretTable,
            'packages' => InternetPackage::where('status', 'active')->orderBy('name')->get(),
            'showDeletedCustomers' => true,
            'perPage' => $perPage,
            'perPageDefault' => 200,
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
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'connection_id' => ['required_with:internet_package_id', 'required_if:use_fixed_ip,1', 'nullable', 'string', 'max:100', 'unique:customers,connection_id'],
            'address' => ['nullable', 'string'],
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
            'mikrotik_router_ids' => ['nullable', 'array'],
            'mikrotik_router_ids.*' => ['integer', 'distinct', 'exists:mikrotik_routers,id'],
            'use_fixed_ip' => ['nullable', 'boolean'],
            'fixed_ip_address' => ['nullable', 'required_if:use_fixed_ip,1', 'ip', 'max:45', 'unique:customers,fixed_ip_address'],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $data['phone'] = trim((string) ($data['phone'] ?? ''));
        $data['address'] = trim((string) ($data['address'] ?? ''));

        $this->normalizeCustomerConnectionData($data);
        $data['is_customer'] = (bool) ($data['is_customer'] ?? false) || ! empty($data['internet_package_id']);
        $data['is_vendor'] = (bool) ($data['is_vendor'] ?? false);
        $this->normalizeResellerData($data);
        $this->ensurePartyHasRole($data);
        $data['never_suspend'] = (bool) ($data['never_suspend'] ?? false);
        $this->normalizeIpMode($data);

        $customer = Customer::create(Arr::except($data, ['mikrotik_router_ids']));
        $customer->mikrotikRouters()->sync($data['mikrotik_router_ids']);

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
        $customer->load(['activeSubscription', 'mikrotikRouters']);

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
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'connection_id' => ['required_with:internet_package_id', 'required_if:use_fixed_ip,1', 'nullable', 'string', 'max:100', Rule::unique('customers', 'connection_id')->ignore($customer->id)],
            'address' => ['nullable', 'string'],
            'map_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:map_longitude'],
            'map_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:map_latitude'],
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
            'mikrotik_router_ids' => ['nullable', 'array'],
            'mikrotik_router_ids.*' => ['integer', 'distinct', 'exists:mikrotik_routers,id'],
            'use_fixed_ip' => ['nullable', 'boolean'],
            'fixed_ip_address' => ['nullable', 'required_if:use_fixed_ip,1', 'ip', 'max:45', Rule::unique('customers', 'fixed_ip_address')->ignore($customer->id)],
            'internet_package_id' => ['nullable', 'exists:internet_packages,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $data['phone'] = trim((string) ($data['phone'] ?? ''));
        $data['address'] = trim((string) ($data['address'] ?? ''));

        $this->normalizeCustomerConnectionData($data, $customer);
        $data['is_customer'] = (bool) ($data['is_customer'] ?? false) || ! empty($data['internet_package_id']);
        $data['is_vendor'] = (bool) ($data['is_vendor'] ?? false);
        $this->normalizeResellerData($data, $customer);
        $this->ensurePartyHasRole($data);
        $data['never_suspend'] = (bool) ($data['never_suspend'] ?? false);
        $this->normalizeIpMode($data);

        DB::transaction(function () use (&$customer, $data, $recordVersionService): void {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $customer->load('mikrotikRouters');
            $oldCommissionPercent = (float) $customer->reseller_commission_percent;
            $activeSubscription = $customer->activeSubscription()->with('package')->lockForUpdate()->first();
            $customer->setRelation('activeSubscription', $activeSubscription);
            $oldSnapshot = $recordVersionService->snapshot($customer, ['activeSubscription.package']);

            $newPackageId = ! empty($data['internet_package_id']) ? (int) $data['internet_package_id'] : null;
            $oldPackageId = $activeSubscription?->internet_package_id ? (int) $activeSubscription->internet_package_id : null;
            $oldRouterIds = $customer->mikrotikRouters->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($oldRouterIds === [] && $customer->mikrotik_router_id) {
                $oldRouterIds = [(int) $customer->mikrotik_router_id];
            }
            $newRouterIds = collect($data['mikrotik_router_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
            $connectionChanged = $customer->connection_id !== ($data['connection_id'] ?? null)
                || $oldRouterIds !== $newRouterIds;
            $switchedFromFixedToDynamic = $customer->use_fixed_ip && ! $data['use_fixed_ip'];
            $customerData = Arr::except($data, ['internet_package_id', 'start_date', 'mikrotik_router_ids']);

            if ($oldPackageId !== $newPackageId || $connectionChanged || $switchedFromFixedToDynamic) {
                $customerData['learned_ip_address'] = null;
                $customerData['learned_ip_package_id'] = null;
            }

            RecordVersionObserver::withoutRecording(fn () => $customer->update($customerData));
            $customer->mikrotikRouters()->sync($data['mikrotik_router_ids']);

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
            'mikrotikRouters',
            'importedSecret',
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

        $routers = MikrotikRouter::query()->orderBy('name')->get();

        return view('customers.show', compact('customer', 'routers'));
    }

    public function inlineUpdate(Request $request, Customer $customer)
    {
        $field = $request->validate([
            'field' => ['required', 'in:name,phone,package,connection_id,address,comment'],
            'value' => ['nullable'],
        ])['field'];

        $validationRules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'package' => ['nullable', 'integer', 'exists:internet_packages,id'],
            'connection_id' => ['nullable', 'string', 'max:100', Rule::unique('customers', 'connection_id')->ignore($customer->id)],
            'address' => ['nullable', 'string', 'max:1000'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];

        $validationMessages = [];
        if ($field === 'name') {
            $validationMessages['value.required'] = 'Customer name is required.';
        }

        $validated = $request->validate([
            'value' => $validationRules[$field],
        ], $validationMessages);

        $value = $validated['value'];

        if ($field === 'connection_id') {
            $normalizedValue = trim((string) $value);
            $customer->update([
                'connection_id' => $normalizedValue === '' ? null : $normalizedValue,
                'mikrotik_username' => $normalizedValue === '' ? null : $normalizedValue,
            ]);

            return response()->json([
                'message' => 'Party updated.',
                'value' => $customer->fresh()->connection_id,
            ]);
        }

        if ($field === 'comment') {
            $importedSecret = $customer->importedSecret()->first();
            if (! $importedSecret) {
                return response()->json([
                    'message' => 'No imported MikroTik record is linked to this party.',
                ], 422);
            }

            $normalizedValue = trim((string) $value);
            $importedSecret->update([
                'router_comment' => $normalizedValue === '' ? null : $normalizedValue,
            ]);

            return response()->json([
                'message' => 'Party updated.',
                'value' => $importedSecret->fresh()->router_comment,
            ]);
        }

        if ($field === 'package') {
            $packageId = blank($value) ? null : (int) $value;
            $package = $packageId ? InternetPackage::query()->find($packageId) : null;

            DB::transaction(function () use ($customer, $packageId): void {
                $activeSubscription = $customer->subscriptions()
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($packageId) {
                    if ($activeSubscription) {
                        $activeSubscription->update([
                            'internet_package_id' => $packageId,
                            'start_date' => $activeSubscription->start_date ?: now()->toDateString(),
                            'end_date' => null,
                        ]);
                    } else {
                        $customer->subscriptions()->create([
                            'internet_package_id' => $packageId,
                            'start_date' => now()->toDateString(),
                            'status' => 'active',
                        ]);
                    }
                } elseif ($activeSubscription) {
                    $activeSubscription->update([
                        'status' => 'inactive',
                        'end_date' => now()->toDateString(),
                    ]);
                }
            });

            $freshCustomer = $customer->fresh(['activeSubscription.package']);
            $currentPackage = $freshCustomer->activeSubscription?->package;

            return response()->json([
                'message' => 'Party updated.',
                'value' => $currentPackage?->name ?? 'No package',
                'package_id' => $currentPackage?->id,
            ]);
        }

        $customer->update([$field => $value]);

        return response()->json([
            'message' => 'Party updated.',
            'value' => $customer->fresh()->{$field},
        ]);
    }

    public function history(Customer $customer)
    {
        $customer = Customer::withTrashed()->findOrFail($customer->getKey());
        $customer->load(['activeSubscription.package']);
        $versions = $customer->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('customers.history', compact('customer', 'versions'));
    }

    public function destroy(Customer $customer)
    {
        $customerName = $customer->name;
        $mikrotikResult = null;
        $mikrotikWarning = null;

        try {
            $mikrotikResult = app(MikrotikCustomerSyncService::class)->remove($customer);
        } catch (Throwable $exception) {
            Log::error('Customer soft-deleted after MikroTik secret removal failed.', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'mikrotik_username' => $customer->mikrotik_username ?: $customer->connection_id,
                'mikrotik_router_id' => $customer->mikrotik_router_id,
                'exception' => $exception,
            ]);
            $mikrotikWarning = 'Party was deleted, but its MikroTik secret could not be removed. The failure was written to the application log.';
        }

        $customer->delete();

        $message = "\"{$customerName}\" moved to deleted parties. ";
        $message .= $mikrotikWarning
            ? 'MikroTik removal failed and was logged. '
            : "MikroTik secret removal result: {$mikrotikResult}. ";
        $message .= 'You can still view its history from Deleted Parties.';

        return redirect()
            ->route('customers.deleted')
            ->with('success', $message)
            ->with('warning', $mikrotikWarning);
    }

    public function deletedHistory(int $customerId)
    {
        $customer = Customer::withTrashed()->findOrFail($customerId);
        $customer->load(['activeSubscription.package']);
        $versions = $customer->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('customers.history', compact('customer', 'versions'));
    }

    public function restore(int $customerId)
    {
        $customer = Customer::withTrashed()->findOrFail($customerId);

        if (! $customer->trashed()) {
            return redirect()
                ->route('customers.index')
                ->with('warning', 'Party is already active.');
        }

        $customer->restore();

        return redirect()
            ->route('customers.index')
            ->with('success', "\"{$customer->name}\" restored successfully.");
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
            now()->format('d/m/Y H:i'),
            Carbon::parse($nextDate)->format('d/m/Y')
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
            ->with('success', 'Package has been activated until '.Carbon::parse($nextDate)->format('d/m/Y').". MikroTik user {$syncResult['status']}.")
            ->with('warning', $syncResult['warning']);
    }

    public function updateServiceValidity(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'service_valid_until' => ['required', 'date'],
            'validity_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $previous = $customer->service_valid_until?->format('d/m/Y') ?? 'not set';
        $newUntil = $data['service_valid_until'];
        $detail = sprintf(
            '[%s] Manual validity override: %s → %s. Reason: %s',
            now()->format('d/m/Y H:i'),
            $previous,
            Carbon::parse($newUntil)->format('d/m/Y'),
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

    public function updateMikrotikTargets(Request $request, Customer $customer)
    {
        if (! $customer->mikrotik_username && ! $customer->connection_id) {
            return back()->withErrors(['mikrotik_router_ids' => 'A Connection ID is required before assigning MikroTik targets.']);
        }

        $data = $request->validate([
            'mikrotik_router_ids' => ['required', 'array', 'min:1'],
            'mikrotik_router_ids.*' => ['integer', 'distinct', 'exists:mikrotik_routers,id'],
        ]);
        $routerIds = collect($data['mikrotik_router_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all();

        DB::transaction(function () use ($customer, $routerIds): void {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $customer->mikrotikRouters()->sync($routerIds);
            $customer->update(['mikrotik_router_id' => $routerIds[0]]);
        });

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());

        return back()
            ->with('success', 'MikroTik targets saved. PPPoE user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    public function forceInactive(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'inactive_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $detail = sprintf(
            '[%s] Service temporarily force-inactivated while validity remained %s. Reason: %s',
            now()->format('d/m/Y H:i'),
            $customer->service_valid_until?->format('d/m/Y') ?? 'not set',
            trim($data['inactive_note'])
        );

        DB::transaction(function () use ($customer, $detail): void {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $subscription = $customer->activeSubscription()->lockForUpdate()->first();

            $customer->update([
                'status' => 'inactive',
                'service_validity_note' => trim(implode("\n", array_filter([$customer->service_validity_note, $detail]))),
                'notes' => trim(implode("\n", array_filter([$customer->notes, $detail]))),
            ]);

            $subscription?->update([
                'status' => 'inactive',
                'end_date' => now()->toDateString(),
            ]);
        });

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());

        return back()
            ->with('success', 'Service temporarily force-inactivated; validity and grace data were kept. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    public function forceActive(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'active_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $subscriptionId = $customer->subscriptions()->latest('id')->value('id');
        if (! $subscriptionId) {
            return back()->withErrors(['active_note' => 'A package subscription is required before temporarily activating this service.']);
        }

        $detail = sprintf(
            '[%s] Service temporarily force-activated while validity remained %s. Reason: %s',
            now()->format('d/m/Y H:i'),
            $customer->service_valid_until?->format('d/m/Y') ?? 'not set',
            trim($data['active_note'])
        );

        DB::transaction(function () use ($customer, $subscriptionId, $detail): void {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $subscription = Subscription::query()->whereKey($subscriptionId)->lockForUpdate()->firstOrFail();

            $customer->update([
                'status' => 'active',
                'service_validity_note' => trim(implode("\n", array_filter([$customer->service_validity_note, $detail]))),
                'notes' => trim(implode("\n", array_filter([$customer->notes, $detail]))),
            ]);
            $subscription->update([
                'status' => 'active',
                'end_date' => null,
            ]);
        });

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());

        return back()
            ->with('success', 'Service temporarily force-activated; validity and grace data were kept. MikroTik user '.$syncResult['status'].'.')
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
        $routerIds = collect($data['mikrotik_router_ids'] ?? (isset($data['mikrotik_router_id']) ? [$data['mikrotik_router_id']] : []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $data['connection_id'] = $connectionId;
        $data['mikrotik_username'] = $connectionId;

        if (! $connectionId) {
            $data['mikrotik_password'] = null;
        } elseif (! $customer || $customer->connection_id !== $connectionId) {
            // A new PPPoE identity starts with the configured default. When an
            // existing identity is unchanged, omit this attribute completely:
            // decrypting and re-encrypting it is unnecessary and can fail when
            // a database was imported from an installation with another APP_KEY.
            $data['mikrotik_password'] = MikrotikCustomerSyncService::DEFAULT_PASSWORD;
        } else {
            unset($data['mikrotik_password']);
        }

        $data['mikrotik_router_ids'] = $connectionId ? $routerIds : [];
        $data['mikrotik_router_id'] = $connectionId ? ($routerIds[0] ?? null) : null;

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

    private function hasImportedSecretTable(): bool
    {
        try {
            return Schema::hasTable('mikrotik_imported_secrets');
        } catch (Throwable) {
            return false;
        }
    }

    private function customerQueryForIndex(Request $request, bool $hasImportedSecretTable, bool $onlyDeleted): Builder
    {
        $query = $onlyDeleted ? Customer::onlyTrashed() : Customer::query();
        $normalizeDate = static function (mixed $value): ?string {
            $date = trim((string) $value);
            if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
                return null;
            }

            return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $date : null;
        };
        $expiringDate = $normalizeDate($request->query('expiring_date'));
        $expiredDate = $normalizeDate($request->query('expired_date'));
        $expiryWindow = in_array($request->query('expiry_window'), [
            'expired_last_7',
            'today',
            'tomorrow',
            'in_7_days',
        ], true) ? $request->query('expiry_window') : null;

        $query
            ->with(['activeSubscription.package', 'latestSubscription.package'])
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
            ->when($expiryWindow === 'expired_last_7', function ($query) {
                $query->whereNotNull('service_valid_until')
                    ->whereDate('service_valid_until', '>=', now()->subDays(7)->toDateString())
                    ->whereDate('service_valid_until', '<', now()->toDateString());
            })
            ->when($expiryWindow === 'today', function ($query) {
                $query->whereDate('service_valid_until', now()->toDateString());
            })
            ->when($expiryWindow === 'tomorrow', function ($query) {
                $query->whereDate('service_valid_until', '>=', now()->toDateString())
                    ->whereDate('service_valid_until', '<=', now()->addDay()->toDateString());
            })
            ->when($expiryWindow === 'in_7_days', function ($query) {
                $query->whereDate('service_valid_until', '>=', now()->toDateString())
                    ->whereDate('service_valid_until', '<=', now()->addDays(7)->toDateString());
            })
            ->when(! $expiryWindow && ($expiringDate || $expiredDate), function ($query) use ($expiringDate, $expiredDate) {
                $today = now()->toDateString();
                $rangeStart = $expiredDate ?: $today;
                $rangeEnd = $expiringDate ?: $today;

                $query->whereNotNull('service_valid_until')
                    ->whereDate('service_valid_until', '>=', $rangeStart)
                    ->whereDate('service_valid_until', '<=', $rangeEnd);
            });

        return $query;
    }
}

