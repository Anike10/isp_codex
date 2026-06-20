<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MikrotikRouter;
use App\Services\MikrotikCustomerSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mikrotik:sync-customers', function (MikrotikCustomerSyncService $syncService) {
    $createdOrUpdated = 0;
    $skippedOrDisabled = 0;
    $failed = 0;

    Customer::query()
        ->with('activeSubscription.package')
        ->orderBy('id')
        ->chunkById(100, function ($customers) use ($syncService, &$createdOrUpdated, &$skippedOrDisabled, &$failed): void {
            foreach ($customers as $customer) {
                if (! $customer->mikrotik_username || ! $customer->mikrotik_password) {
                    $customer->forceFill([
                        'mikrotik_username' => $customer->connection_id,
                        'mikrotik_password' => MikrotikCustomerSyncService::DEFAULT_PASSWORD,
                    ])->save();
                }

                try {
                    $status = $syncService->sync($customer->refresh());

                    if (str_contains($status, 'created') || str_contains($status, 'updated')) {
                        $createdOrUpdated++;
                    } else {
                        $skippedOrDisabled++;
                    }

                    $this->line("{$customer->connection_id}: {$status}");
                } catch (\Throwable $exception) {
                    $failed++;
                    $this->error("{$customer->connection_id}: failed - {$exception->getMessage()}");
                }
            }
        });

    $this->info("MikroTik sync finished. Created/updated: {$createdOrUpdated}. Skipped/disabled: {$skippedOrDisabled}. Failed: {$failed}.");

    return $failed === 0 ? self::SUCCESS : self::FAILURE;
})->purpose('Create or update MikroTik PPPoE users for all customers');

Artisan::command('mikrotik:sync-router-users {--force : Sync every active router now, ignoring interval}', function (MikrotikCustomerSyncService $syncService) {
    $synced = 0;
    $failed = 0;

    MikrotikRouter::where('status', 'active')
        ->orderBy('id')
        ->get()
        ->each(function (MikrotikRouter $router) use ($syncService, &$synced, &$failed): void {
            $isDue = $this->option('force')
                || ! $router->last_pppoe_sync_at
                || $router->last_pppoe_sync_at->addMinutes($router->pppoe_sync_interval_minutes)->lte(now());

            if (! $isDue) {
                $this->line("{$router->name} ({$router->ip_address}): skipped until next interval.");

                return;
            }

            try {
                $summary = $syncService->syncRouter($router);
                $summaryText = "created={$summary['created']}, updated={$summary['updated']}, inactive_profile={$summary['moved_inactive']}, skipped={$summary['skipped']}, failed={$summary['failed']}";

                $router->update([
                    'last_pppoe_sync_at' => now(),
                    'last_pppoe_sync_summary' => $summaryText.($summary['messages'] ? ' | '.implode(' | ', array_slice($summary['messages'], 0, 5)) : ''),
                ]);

                $synced++;
                $this->line("{$router->name} ({$router->ip_address}): {$summaryText}");
            } catch (\Throwable $exception) {
                $failed++;
                $router->update([
                    'last_pppoe_sync_at' => now(),
                    'last_pppoe_sync_summary' => 'failed: '.$exception->getMessage(),
                ]);
                $this->error("{$router->name} ({$router->ip_address}): failed - {$exception->getMessage()}");
            }
        });

    $this->info("Router PPPoE sync finished. Synced: {$synced}. Failed: {$failed}.");

    return $failed === 0 ? self::SUCCESS : self::FAILURE;
})->purpose('Verify and sync PPPoE users on MikroTik routers by each router interval');

Artisan::command('billing:disable-overdue-customers {--date= : Cutoff date, defaults to today}', function (MikrotikCustomerSyncService $syncService) {
    $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();
    $disabled = 0;

    $overdueCustomerIds = Invoice::query()
        ->where('due_amount', '>', 0)
        ->where(function ($query) use ($date) {
            $query->whereDate('due_date', '<=', $date)
                ->orWhere(function ($query) use ($date) {
                    $query->whereNull('due_date')
                        ->where('invoice_type', 'service')
                        ->where('billing_month', '<=', substr($date, 0, 7));
                });
        })
        ->whereHas('customer', fn ($query) => $query
            ->where('never_suspend', false)
            ->where(function ($query) use ($date) {
                $query->whereNull('grace_until')
                    ->orWhereDate('grace_until', '<', $date);
            }))
        ->distinct()
        ->pluck('customer_id');

    $expiredGraceCustomerIds = Customer::query()
        ->where('status', 'active')
        ->where('never_suspend', false)
        ->whereNotNull('grace_until')
        ->whereDate('grace_until', '<', $date)
        ->pluck('id');

    $customerIds = $overdueCustomerIds
        ->merge($expiredGraceCustomerIds)
        ->unique()
        ->values();

    Customer::whereIn('id', $customerIds)
        ->with('activeSubscription.package')
        ->chunkById(100, function ($customers) use ($syncService, &$disabled): void {
            foreach ($customers as $customer) {
                $customer->update(['status' => 'inactive']);

                if ($customer->activeSubscription) {
                    $customer->activeSubscription->update([
                        'status' => 'inactive',
                        'end_date' => now()->toDateString(),
                    ]);
                }

                try {
                    $syncService->sync($customer->refresh());
                } catch (\Throwable $exception) {
                    $this->warn("{$customer->connection_id}: disabled locally, MikroTik sync failed - {$exception->getMessage()}");
                }

                $disabled++;
                $this->line("{$customer->connection_id}: disabled for overdue invoice.");
            }
        });

    $this->info("Overdue disable finished. Disabled customers: {$disabled}.");

    return self::SUCCESS;
})->purpose('Disable non-special customers with overdue due invoices and sync MikroTik inactive profile');
