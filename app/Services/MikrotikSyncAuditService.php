<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\MikrotikSyncFailure;
use App\Models\MikrotikSyncIssue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MikrotikSyncAuditService
{
    public function recordMismatch(
        MikrotikRouter $router,
        Customer $customer,
        string $expectedProfile,
        ?string $actualProfile,
        string $details
    ): MikrotikSyncIssue {
        return DB::transaction(function () use ($router, $customer, $expectedProfile, $actualProfile, $details): MikrotikSyncIssue {
            $issue = $this->openIssue($router, $customer);
            $issue->forceFill([
                'username' => $this->username($customer),
                'issue_type' => 'secret_mismatch',
                'expected_profile' => $expectedProfile,
                'actual_profile' => $actualProfile,
                'details' => $details,
                'last_detected_at' => now(),
            ])->save();

            return $issue;
        });
    }

    public function recordFailure(
        MikrotikRouter $router,
        ?Customer $customer,
        string $context,
        Throwable|string $error
    ): MikrotikSyncIssue {
        $message = $this->errorMessage($error);

        $issue = DB::transaction(function () use ($router, $customer, $context, $message): MikrotikSyncIssue {
            $issue = $this->openIssue($router, $customer);
            $issue->forceFill([
                'username' => $customer ? $this->username($customer) : null,
                'issue_type' => $issue->exists && $issue->issue_type === 'secret_mismatch'
                    ? 'secret_mismatch'
                    : 'sync_failed',
                'details' => $issue->details ?: 'MikroTik sync could not be completed.',
                'last_error' => $message,
                'attempt_count' => (int) $issue->attempt_count + 1,
                'last_detected_at' => now(),
                'last_attempted_at' => now(),
            ])->save();

            MikrotikSyncFailure::query()->create([
                'mikrotik_sync_issue_id' => $issue->id,
                'mikrotik_router_id' => $router->id,
                'customer_id' => $customer?->id,
                'username' => $customer ? $this->username($customer) : null,
                'context' => $context,
                'error_message' => $message,
                'attempted_at' => now(),
            ]);

            return $issue;
        });

        Log::warning('MikroTik sync attempt failed.', [
            'router_id' => $router->id,
            'customer_id' => $customer?->id,
            'username' => $customer ? $this->username($customer) : null,
            'context' => $context,
            'error' => $message,
        ]);

        return $issue;
    }

    public function resolveCustomer(MikrotikRouter $router, Customer $customer): void
    {
        $this->resolveByKey($this->issueKey($router, $customer));
    }

    public function resolveCustomerAfterAttempt(MikrotikRouter $router, Customer $customer): void
    {
        MikrotikSyncIssue::query()
            ->where('issue_key', $this->issueKey($router, $customer))
            ->whereNull('resolved_at')
            ->update([
                'attempt_count' => DB::raw('attempt_count + 1'),
                'last_attempted_at' => now(),
                'resolved_at' => now(),
                'last_error' => null,
                'updated_at' => now(),
            ]);
    }

    public function resolveRouter(MikrotikRouter $router): void
    {
        $this->resolveByKey($this->issueKey($router));
    }

    private function openIssue(MikrotikRouter $router, ?Customer $customer = null): MikrotikSyncIssue
    {
        $issue = MikrotikSyncIssue::query()->firstOrNew([
            'issue_key' => $this->issueKey($router, $customer),
        ]);

        if (! $issue->exists || $issue->resolved_at !== null) {
            $issue->forceFill([
                'mikrotik_router_id' => $router->id,
                'customer_id' => $customer?->id,
                'username' => $customer ? $this->username($customer) : null,
                'issue_type' => 'sync_failed',
                'expected_profile' => null,
                'actual_profile' => null,
                'details' => null,
                'last_error' => null,
                'attempt_count' => 0,
                'first_detected_at' => now(),
                'last_detected_at' => now(),
                'last_attempted_at' => null,
                'resolved_at' => null,
            ]);
        }

        return $issue;
    }

    private function resolveByKey(string $issueKey): void
    {
        MikrotikSyncIssue::query()
            ->where('issue_key', $issueKey)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'last_error' => null,
                'updated_at' => now(),
            ]);
    }

    private function issueKey(MikrotikRouter $router, ?Customer $customer = null): string
    {
        return hash('sha256', 'router:'.$router->id.'|customer:'.($customer?->id ?? 'router'));
    }

    private function username(Customer $customer): string
    {
        return trim((string) ($customer->mikrotik_username ?: $customer->connection_id));
    }

    private function errorMessage(Throwable|string $error): string
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        return trim(preg_replace('/\s+/', ' ', $message)) ?: 'Unknown MikroTik sync failure.';
    }
}
