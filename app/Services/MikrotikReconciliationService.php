<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\MikrotikSyncIssue;
use Illuminate\Support\Collection;
use Throwable;

class MikrotikReconciliationService
{
    public function __construct(
        private readonly MikrotikCustomerSyncService $customerSync,
        private readonly MikrotikImportService $importService,
        private readonly MikrotikSyncAuditService $syncAudit,
    ) {}

    /**
     * @return array{routers: int, customers: int, mismatches: int, corrected: int, failed: int, results: array<int, array<string, mixed>>}
     */
    public function reconcileAll(): array
    {
        return $this->reconcileRouters(
            MikrotikRouter::query()
                ->where('status', 'active')
                ->where('read_only', false)
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * Retry every router that still has at least one unresolved sync issue.
     *
     * @return array{routers: int, customers: int, mismatches: int, corrected: int, failed: int, results: array<int, array<string, mixed>>}
     */
    public function retryUnresolved(): array
    {
        $routerIds = MikrotikSyncIssue::query()
            ->whereNull('resolved_at')
            ->distinct()
            ->pluck('mikrotik_router_id');

        return $this->reconcileRouters(
            MikrotikRouter::query()
                ->whereIn('id', $routerIds)
                ->where('status', 'active')
                ->where('read_only', false)
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * @return array{router: string, customers: int, mismatches: int, corrected: int, failed: int, messages: array<int, string>}
     */
    public function reconcileRouter(MikrotikRouter $router): array
    {
        $startedAt = now()->startOfSecond();
        $customers = Customer::query()
            ->with('activeSubscription.package')
            ->assignedToMikrotikRouter($router->id)
            ->orderBy('id')
            ->get();

        $beforeMismatchKeys = collect();
        $messages = [];
        $failed = 0;

        try {
            $before = $this->importService->liveRecords($router, '/ppp/secret/print');
            $beforeMismatchKeys = $this->inspectCustomers($router, $customers, $before);
            $this->syncAudit->resolveRouter($router);
        } catch (Throwable $exception) {
            $failed++;
            $messages[] = 'Initial secret check failed: '.$exception->getMessage();
            $this->syncAudit->recordFailure($router, null, 'nightly_reconciliation_read_before', $exception);
        }

        try {
            $syncSummary = $this->customerSync->syncRouter($router);
            $failed += (int) ($syncSummary['failed'] ?? 0);
            foreach ($syncSummary['messages'] ?? [] as $message) {
                $messages[] = $message;
            }
        } catch (Throwable $exception) {
            $failed++;
            $messages[] = 'Router sync failed: '.$exception->getMessage();
            $this->syncAudit->recordFailure($router, null, 'nightly_reconciliation_sync', $exception);
        }

        $afterMismatchKeys = collect();
        try {
            $after = $this->importService->liveRecords($router, '/ppp/secret/print');
            $afterMismatchKeys = $this->inspectCustomers($router, $customers, $after);
            $this->syncAudit->resolveRouter($router);

            $unloggedIssues = MikrotikSyncIssue::query()
                ->where('mikrotik_router_id', $router->id)
                ->whereIn('customer_id', $afterMismatchKeys)
                ->whereNull('resolved_at')
                ->where(function ($query) use ($startedAt): void {
                    $query->whereNull('last_attempted_at')
                        ->orWhere('last_attempted_at', '<', $startedAt);
                })
                ->with('customer')
                ->get();

            foreach ($unloggedIssues as $issue) {
                if (! $issue->customer) {
                    continue;
                }

                $failed++;
                $message = 'Mismatch remains after reconciliation verification. '.($issue->details ?: 'RouterOS still differs from the app.');
                $messages[] = "{$issue->username}: {$message}";
                $this->syncAudit->recordFailure(
                    $router,
                    $issue->customer,
                    'nightly_reconciliation_unresolved',
                    $message
                );
            }
        } catch (Throwable $exception) {
            $failed++;
            $messages[] = 'Verification check failed: '.$exception->getMessage();
            $this->syncAudit->recordFailure($router, null, 'nightly_reconciliation_verify', $exception);
        }

        return [
            'router' => $router->name,
            'customers' => $customers->count(),
            'mismatches' => $beforeMismatchKeys->count(),
            'corrected' => $beforeMismatchKeys->diff($afterMismatchKeys)->count(),
            'failed' => $failed,
            'messages' => $messages,
        ];
    }

    /**
     * @param  Collection<int, MikrotikRouter>  $routers
     * @return array{routers: int, customers: int, mismatches: int, corrected: int, failed: int, results: array<int, array<string, mixed>>}
     */
    private function reconcileRouters(Collection $routers): array
    {
        $summary = [
            'routers' => 0,
            'customers' => 0,
            'mismatches' => 0,
            'corrected' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($routers as $router) {
            $result = $this->reconcileRouter($router);
            $summary['routers']++;
            $summary['customers'] += $result['customers'];
            $summary['mismatches'] += $result['mismatches'];
            $summary['corrected'] += $result['corrected'];
            $summary['failed'] += $result['failed'];
            $summary['results'][] = $result;
        }

        return $summary;
    }

    /**
     * @param  Collection<int, Customer>  $customers
     * @param  array<int, array<string, mixed>>  $records
     * @return Collection<int, string>
     */
    private function inspectCustomers(MikrotikRouter $router, Collection $customers, array $records): Collection
    {
        $byName = collect($records)
            ->filter(fn (array $record): bool => filled($record['name'] ?? null))
            ->groupBy(fn (array $record): string => mb_strtolower(trim((string) $record['name'])));
        $mismatches = collect();

        foreach ($customers as $customer) {
            $username = trim((string) ($customer->mikrotik_username ?: $customer->connection_id));
            if ($username === '') {
                continue;
            }

            $expectedProfile = $this->customerSync->expectedProfile($customer, $router);
            $matches = $byName->get(mb_strtolower($username), collect());
            $details = [];
            $actualProfile = null;

            if ($matches->isEmpty()) {
                $details[] = 'PPPoE secret is missing from the router.';
                $actualProfile = '[missing]';
            } elseif ($matches->count() > 1) {
                $details[] = 'Duplicate PPPoE secrets exist for this username.';
                $actualProfile = '[multiple]';
            } else {
                $secret = $matches->first();
                $actualProfile = trim((string) ($secret['profile'] ?? ''));

                if (trim((string) ($secret['name'] ?? '')) !== $username) {
                    $details[] = 'Router username letter case does not exactly match the app.';
                }
                if ($actualProfile !== trim($expectedProfile)) {
                    $details[] = "Profile is {$actualProfile}; expected {$expectedProfile}.";
                }
                if ($this->routerBoolean($secret['disabled'] ?? false)) {
                    $details[] = 'PPPoE secret is disabled; the app expects it to be enabled.';
                }
                if (filled($secret['service'] ?? null) && trim((string) $secret['service']) !== 'pppoe') {
                    $details[] = 'PPP service is not pppoe.';
                }
            }

            if ($details !== []) {
                $this->syncAudit->recordMismatch(
                    $router,
                    $customer,
                    $expectedProfile,
                    $actualProfile,
                    implode(' ', $details)
                );
                $mismatches->push((string) $customer->id);
            } else {
                $this->syncAudit->resolveCustomer($router, $customer);
            }
        }

        return $mismatches->unique()->values();
    }

    private function routerBoolean(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['true', 'yes', '1', 'on'], true);
    }
}
