<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\ResellerCommissionHistory;
use App\Models\Subscription;
use App\Observers\RecordVersionObserver;
use App\Services\ConcessionLogService;
use App\Services\MikrotikCustomerSyncService;
use App\Services\RecordVersionService;
use App\Support\PartyNote;
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
        $expiryCountQuery = Customer::query()
            ->where('never_suspend', false)
            ->whereNotNull('service_valid_until');
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
        if ($data['never_suspend'] && ! $request->user()?->hasPermission('mark_special_customer')) {
            $data['never_suspend'] = false;
        }
        if ($data['never_suspend']) {
            // A special ISP customer is never suspended, so the line stays active.
            $data['status'] = 'active';
        }
        $this->normalizeIpMode($data);

        $customer = Customer::create(Arr::except($data, ['mikrotik_router_ids']));
        $customer->mikrotikRouters()->sync($data['mikrotik_router_ids']);

        if ($customer->never_suspend) {
            app(ConcessionLogService::class)->recordSpecialToggle($customer, true, 'Set while creating the party.');
        }

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
            'custom_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $maySetSpecialPrice = (bool) $request->user()?->hasPermission('set_special_package_price');
        // false = not submitted / not allowed -> leave the subscription price alone.
        $customPriceInput = false;
        if ($maySetSpecialPrice && $request->has('custom_price')) {
            $raw = $request->input('custom_price');
            $customPriceInput = ($raw === null || $raw === '') ? null : round((float) $raw, 2);
        }

        $data['phone'] = trim((string) ($data['phone'] ?? ''));
        $data['address'] = trim((string) ($data['address'] ?? ''));

        $previousMikrotikUsername = $customer->mikrotik_username ?: $customer->connection_id;

        $this->normalizeCustomerConnectionData($data, $customer);
        $data['is_customer'] = (bool) ($data['is_customer'] ?? false) || ! empty($data['internet_package_id']);
        $data['is_vendor'] = (bool) ($data['is_vendor'] ?? false);
        $this->normalizeResellerData($data, $customer);
        $this->ensurePartyHasRole($data);
        $data['never_suspend'] = (bool) ($data['never_suspend'] ?? false);
        $wasSpecial = (bool) $customer->never_suspend;
        if ($data['never_suspend'] !== $wasSpecial && ! $request->user()?->hasPermission('mark_special_customer')) {
            $data['never_suspend'] = $wasSpecial;
        }
        if ($data['never_suspend']) {
            // A special ISP customer is never suspended, so keep the line active
            // and reconnect it to the service profile on the next sync.
            $data['status'] = 'active';
        }
        $this->normalizeIpMode($data);

        DB::transaction(function () use (&$customer, $data, $recordVersionService, $customPriceInput): void {
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
            $customerData = Arr::except($data, ['internet_package_id', 'start_date', 'mikrotik_router_ids', 'custom_price']);

            if ($oldPackageId !== $newPackageId || $connectionChanged || $switchedFromFixedToDynamic) {
                $customerData['learned_ip_address'] = null;
                $customerData['learned_ip_package_id'] = null;
                $customerData['last_connected_ip'] = null;
                $customerData['last_connected_at'] = null;
            }

            RecordVersionObserver::withoutRecording(fn () => $customer->update($customerData));
            $customer->mikrotikRouters()->sync($data['mikrotik_router_ids']);

            if (! empty($data['internet_package_id'])) {
                if ($activeSubscription) {
                    $activeSubscription->update([
                        'internet_package_id' => $data['internet_package_id'],
                        // A special price belongs to one package; drop it when the
                        // party is moved to a different package.
                        'custom_price' => (int) $data['internet_package_id'] === (int) $activeSubscription->internet_package_id
                            ? $activeSubscription->custom_price
                            : null,
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
            } elseif ($activeSubscription && ! $data['never_suspend']) {
                $activeSubscription->update([
                    'status' => 'inactive',
                    'end_date' => now()->toDateString(),
                ]);
            }

            // Apply the special price (or clear it) on whichever subscription now
            // drives billing. Runs after the package block so it overrides the
            // "drop the price on package change" default when the admin set one.
            if ($customPriceInput !== false) {
                $billingSubscription = $customer->activeSubscription()->latest('id')->first()
                    ?: $customer->subscriptions()->latest('id')->first();
                $billingSubscription?->update(['custom_price' => $customPriceInput]);
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

        if ($data['never_suspend']) {
            // Ensure a special customer always has one live subscription so the
            // MikroTik sync can put the PPPoE user back on the service profile.
            $customer->refresh();
            if (! $customer->activeSubscription()->exists()) {
                Subscription::where('customer_id', $customer->id)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->update(['status' => 'active', 'end_date' => null]);
            }
        }

        if ($data['never_suspend'] !== $wasSpecial) {
            app(ConcessionLogService::class)->recordSpecialToggle($customer->refresh(), $data['never_suspend'], 'Changed from party edit.');
        }

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());
        $staleWarning = $this->removeStaleMikrotikUsername($customer, $previousMikrotikUsername);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Party updated successfully. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', collect([$syncResult['warning'], $staleWarning])->filter()->implode(' ') ?: null);
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
            'invoices' => fn ($query) => $query->latest()->with([
                'entryByUser:id,name',
                'payments' => fn ($paymentQuery) => $paymentQuery->oldest('id')->with('entryByUser:id,name'),
            ]),
            'concessionLogs' => fn ($query) => $query->with(['user:id,name', 'package:id,name'])->latest('id'),
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
            'field' => ['required', 'in:name,phone,package,connection_id,address,comment,notes'],
            'value' => ['nullable'],
        ])['field'];

        $validationRules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'package' => ['nullable', 'integer', 'exists:internet_packages,id'],
            'connection_id' => ['nullable', 'string', 'max:100', Rule::unique('customers', 'connection_id')->ignore($customer->id)],
            'address' => ['nullable', 'string', 'max:1000'],
            'comment' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            $previousUsername = $customer->mikrotik_username ?: $customer->connection_id;
            $normalizedValue = trim((string) $value);
            $customer->update([
                'connection_id' => $normalizedValue === '' ? null : $normalizedValue,
                'mikrotik_username' => $normalizedValue === '' ? null : $normalizedValue,
                // The PPPoE username changed, so any learned dynamic IP is stale.
                'learned_ip_address' => null,
                'learned_ip_package_id' => null,
                'last_connected_ip' => null,
                'last_connected_at' => null,
            ]);

            $sync = $this->syncMikrotikCustomer($customer);
            $staleWarning = $this->removeStaleMikrotikUsername($customer, $previousUsername);
            $warning = collect([$sync['warning'], $staleWarning])->filter()->implode(' ');

            return response()->json([
                'message' => 'Party updated. MikroTik user '.$sync['status'].'.',
                'value' => $customer->fresh()->connection_id,
                'warning' => $warning ?: null,
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
                            // Moving to another package drops any special price.
                            'custom_price' => $packageId === (int) $activeSubscription->internet_package_id
                                ? $activeSubscription->custom_price
                                : null,
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

            $sync = $this->syncMikrotikCustomer($freshCustomer);

            return response()->json([
                'message' => 'Party updated. MikroTik user '.$sync['status'].'.',
                'value' => $currentPackage?->name ?? 'No package',
                'package_id' => $currentPackage?->id,
                'warning' => $sync['warning'],
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

        // The party is gone; drop its local mirror of the router secret. If the
        // router still has the secret (removal failed above), the next "Refresh
        // secrets" re-imports it as an unmanaged row the Router Users screen can
        // clean up — otherwise it would sit there forever showing "✓ Linked" to
        // a deleted party with no delete button.
        MikrotikImportedSecret::where('customer_id', $customer->id)->delete();

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

        // The PPPoE secret was removed when the party was deleted, so recreate
        // it on the router now that the party is active again.
        $sync = $this->syncMikrotikCustomer($customer);

        return redirect()
            ->route('customers.index')
            ->with('success', "\"{$customer->name}\" restored successfully. MikroTik user {$sync['status']}.")
            ->with('warning', $sync['warning']);
    }

    public function grantGracePeriod(Request $request, Customer $customer, ConcessionLogService $concessionLog)
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

        $concessionLog->recordGracePeriod($customer, $subscription, (int) $data['grace_days'], null);

        $syncResult = $this->syncMikrotikCustomer($customer);

        return back()
            ->with('success', 'Grace period added. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    public function activateUntilNextDate(Request $request, Customer $customer, ConcessionLogService $concessionLog)
    {
        $subscription = $customer->activeSubscription ?: $customer->subscriptions()->with('package')->latest()->first();
        $data = $request->validate([
            'active_until' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        if (! $subscription || ! $subscription->package) {
            return back()->withErrors(['active_until' => 'No package found for this customer to activate.']);
        }

        $previousValidUntil = $customer->service_valid_until;
        $nextDate = $data['active_until'] ?? now()->addMonthNoOverflow()->toDateString();
        $detail = PartyNote::stamp(sprintf(
            'Activated package to %s via quick-activate action.',
            Carbon::parse($nextDate)->format('d/m/Y')
        ));

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

        $concessionLog->closeOpenForceActive($customer, now(), 'quick_activate');
        $concessionLog->recordValidityChange(
            $customer,
            $subscription,
            'quick_activate',
            $previousValidUntil,
            Carbon::parse($nextDate),
            null,
        );

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());

        return back()
            ->with('success', 'Package has been activated until '.Carbon::parse($nextDate)->format('d/m/Y').". MikroTik user {$syncResult['status']}.")
            ->with('warning', $syncResult['warning']);
    }

    public function updateServiceValidity(Request $request, Customer $customer, ConcessionLogService $concessionLog)
    {
        $data = $request->validate([
            'service_valid_until' => ['required', 'date'],
            'validity_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $previousValidUntil = $customer->service_valid_until;
        $previous = $previousValidUntil?->format('d/m/Y') ?? 'not set';
        $newUntil = $data['service_valid_until'];
        $detail = PartyNote::stamp(sprintf(
            'Manual validity override: %s → %s. Reason: %s',
            $previous,
            Carbon::parse($newUntil)->format('d/m/Y'),
            trim($data['validity_note'])
        ));

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

        $concessionLog->closeOpenForceActive($customer, now(), 'validity_override');
        $concessionLog->recordValidityChange(
            $customer,
            $subscription,
            'validity_override',
            $previousValidUntil,
            Carbon::parse($newUntil),
            trim($data['validity_note']),
        );

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

    public function forceInactive(Request $request, Customer $customer, ConcessionLogService $concessionLog)
    {
        if ($customer->never_suspend) {
            return back()->withErrors(['inactive_note' => 'Remove the Special ISP flag before forcing this party inactive.']);
        }

        $data = $request->validate([
            'inactive_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $detail = PartyNote::stamp(sprintf(
            'Service temporarily force-inactivated while validity remained %s. Reason: %s',
            $customer->service_valid_until?->format('d/m/Y') ?? 'not set',
            trim($data['inactive_note'])
        ));

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

        $concessionLog->recordForceInactive($customer->refresh(), null, trim($data['inactive_note']));

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());

        return back()
            ->with('success', 'Service temporarily force-inactivated; validity and grace data were kept. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    public function forceActive(Request $request, Customer $customer, ConcessionLogService $concessionLog)
    {
        $data = $request->validate([
            'active_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $subscriptionId = $customer->subscriptions()->latest('id')->value('id');
        if (! $subscriptionId) {
            return back()->withErrors(['active_note' => 'A package subscription is required before temporarily activating this service.']);
        }

        $detail = PartyNote::stamp(sprintf(
            'Service temporarily force-activated while validity remained %s. Reason: %s',
            $customer->service_valid_until?->format('d/m/Y') ?? 'not set',
            trim($data['active_note'])
        ));

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

        $concessionLog->recordForceActive(
            $customer->refresh(),
            Subscription::with('package')->find($subscriptionId),
            trim($data['active_note']),
        );

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());

        return back()
            ->with('success', 'Service temporarily force-activated; validity and grace data were kept. MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    /**
     * One-click Special ISP (never-suspend) toggle from the party list. Mirrors
     * the special-flag side effects of a full party edit: a special line is
     * kept active with a live subscription, and the concession log records the
     * running give-away.
     */
    public function toggleSpecial(Request $request, Customer $customer, ConcessionLogService $concessionLog)
    {
        $makeSpecial = ! $customer->never_suspend;

        DB::transaction(function () use (&$customer, $makeSpecial): void {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $customer->update($makeSpecial
                ? ['never_suspend' => true, 'status' => 'active']
                : ['never_suspend' => false]);

            if ($makeSpecial && ! $customer->activeSubscription()->exists()) {
                Subscription::where('customer_id', $customer->id)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->update(['status' => 'active', 'end_date' => null]);
            }
        });

        $concessionLog->recordSpecialToggle($customer->refresh(), $makeSpecial, 'Toggled from the party list.');

        $syncResult = $this->syncMikrotikCustomer($customer->refresh());

        return back()
            ->with('success', ($makeSpecial
                ? '"'.$customer->name.'" is now a Special ISP customer.'
                : 'Special ISP flag removed from "'.$customer->name.'".')
                .' MikroTik user '.$syncResult['status'].'.')
            ->with('warning', $syncResult['warning']);
    }

    /**
     * Set (or clear, with a blank value) this party's special package price.
     * It replaces the package list price in every future billing calculation,
     * renewal, bulk payment, reseller commission and concession value.
     */
    public function updateSpecialPrice(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'custom_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $subscription = $customer->activeSubscription()->with('package')->first()
            ?: $customer->subscriptions()->with('package')->latest('id')->first();

        if (! $subscription) {
            $message = 'Assign a package to this party before setting a special price.';

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['custom_price' => $message]);
        }

        $raw = $data['custom_price'] ?? null;
        $newPrice = ($raw === null || $raw === '') ? null : round((float) $raw, 2);
        $listPrice = round((float) ($subscription->package->monthly_price ?? 0), 2);

        $subscription->update(['custom_price' => $newPrice]);

        $note = $newPrice === null
            ? PartyNote::stamp(sprintf(
                'Special package price cleared; list price BDT %s now applies.',
                number_format($listPrice, 2),
            ))
            : PartyNote::stamp(sprintf(
                'Special package price set to BDT %s (list BDT %s) for %s.',
                number_format($newPrice, 2),
                number_format($listPrice, 2),
                $subscription->package->name ?? 'the current package',
            ));
        $customer->update([
            'notes' => trim(implode("\n", array_filter([$customer->notes, $note]))),
        ]);

        $successMessage = $newPrice === null
            ? 'Special price removed. Billing now uses the package list price.'
            : 'Special price set to BDT '.number_format($newPrice, 2).'. It applies to all future billing for this party.';

        $trim = fn (float $n) => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $successMessage,
                'has_special' => $newPrice !== null,
                'special_price' => $newPrice,
                'special_price_formatted' => $trim($newPrice ?? $listPrice),
                'list_price_formatted' => $trim($listPrice),
            ]);
        }

        return back()->with('success', $successMessage);
    }

    /**
     * One double-click IP control from the party list:
     *  - Auto (dynamic) party with a learned IP  -> pin that live IP as fixed.
     *  - Fixed party                             -> release it back to Auto.
     */
    public function assignLiveIp(Request $request, Customer $customer)
    {
        $wantsJson = $request->wantsJson();

        if ($customer->use_fixed_ip) {
            $customer->update(['use_fixed_ip' => false, 'fixed_ip_address' => null]);
            $sync = $this->syncMikrotikCustomer($customer->refresh());
            $liveIp = $customer->last_connected_ip ?: $customer->learned_ip_address;

            $message = 'IP released. This party is back on Auto (pool) addressing. MikroTik user '.$sync['status'].'.';

            return $wantsJson
                ? response()->json(['message' => $message, 'is_fixed' => false, 'ip' => $liveIp, 'warning' => $sync['warning']])
                : back()->with('success', $message)->with('warning', $sync['warning']);
        }

        $liveIp = $customer->last_connected_ip ?: $customer->learned_ip_address;

        if (! $liveIp) {
            $message = 'No learned IP for this party yet, so there is nothing to pin.';

            return $wantsJson
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['fixed_ip_address' => $message]);
        }

        if (Customer::query()->where('fixed_ip_address', $liveIp)->whereKeyNot($customer->id)->exists()) {
            $message = "The IP {$liveIp} is already pinned to another party.";

            return $wantsJson
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['fixed_ip_address' => $message]);
        }

        $customer->update(['use_fixed_ip' => true, 'fixed_ip_address' => $liveIp]);
        $sync = $this->syncMikrotikCustomer($customer->refresh());

        $message = "IP {$liveIp} pinned as this party's fixed address. MikroTik user ".$sync['status'].'.';

        return $wantsJson
            ? response()->json(['message' => $message, 'is_fixed' => true, 'ip' => $liveIp, 'warning' => $sync['warning']])
            : back()->with('success', $message)->with('warning', $sync['warning']);
    }

    private function syncMikrotikCustomer(Customer $customer): array
    {
        if (method_exists($customer, 'trashed') && $customer->trashed()) {
            // A deleted party has no live MikroTik user to push to; allow the
            // local edit but do not touch any router.
            return [
                'status' => 'skipped (deleted party)',
                'warning' => null,
            ];
        }

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

    /**
     * After a party's PPPoE username changed, delete the secret left behind on
     * the router(s) under the OLD name and drop its imported-secret row.
     *
     * Best effort: it never blocks the edit. On a router failure it returns a
     * warning so the operator knows to finish the job from the Router Users
     * screen once the box is reachable.
     */
    private function removeStaleMikrotikUsername(Customer $customer, ?string $previousUsername): ?string
    {
        $previousUsername = trim((string) $previousUsername);
        $currentUsername = trim((string) ($customer->mikrotik_username ?: $customer->connection_id));

        if ($previousUsername === '' || strcasecmp($previousUsername, $currentUsername) === 0) {
            return null;
        }

        $warning = null;
        if (MikrotikRouter::where('status', 'active')->exists()) {
            try {
                app(MikrotikCustomerSyncService::class)->removeUsername($customer->refresh(), $previousUsername);
            } catch (Throwable $exception) {
                $warning = 'The old MikroTik user "'.$previousUsername.'" could not be removed automatically ('
                    .$exception->getMessage().'). Remove it from the Router Users screen once the router is reachable.';
            }
        }

        $routerIds = $customer->mikrotikRouters()->pluck('mikrotik_routers.id')->all();
        if ($customer->mikrotik_router_id) {
            $routerIds[] = (int) $customer->mikrotik_router_id;
        }

        MikrotikImportedSecret::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower($previousUsername)])
            ->when($routerIds !== [], fn ($query) => $query->whereIn('mikrotik_router_id', array_unique($routerIds)))
            ->delete();

        return $warning;
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
                        ->orWhere('mikrotik_username', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
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
            ->when($expiryWindow || $expiringDate || $expiredDate, function ($query) {
                $query->where('never_suspend', false);
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
