<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MikrotikRouter;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
use App\Services\MikrotikImportService;
use App\Support\BillingWindow;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:super-admin {email} {--revoke : Remove super admin instead of granting it}', function () {
    $email = (string) $this->argument('email');
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error("No user found with email {$email}.");

        return 1;
    }

    $grant = ! $this->option('revoke');

    if (! $grant && User::query()->where('is_super_admin', true)->whereKeyNot($user->id)->count() === 0) {
        $this->error('Refusing to revoke the last remaining super admin.');

        return 1;
    }

    $user->forceFill(['is_super_admin' => $grant])->save();

    $this->info($grant
        ? "{$user->name} <{$email}> is now a super admin."
        : "Super admin access removed from {$user->name} <{$email}>.");

    return 0;
})->purpose('Grant or revoke super admin access for a user by email');

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
                } catch (Throwable $exception) {
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
                $summaryText = "active_sessions={$summary['active_sessions_captured']}, created={$summary['created']}, updated={$summary['updated']}, inactive_profile={$summary['moved_inactive']}, skipped={$summary['skipped']}, failed={$summary['failed']}";

                $router->update([
                    'last_pppoe_sync_at' => now(),
                    'last_pppoe_sync_summary' => $summaryText.($summary['messages'] ? ' | '.implode(' | ', array_slice($summary['messages'], 0, 5)) : ''),
                ]);

                $synced++;
                $this->line("{$router->name} ({$router->ip_address}): {$summaryText}");
            } catch (Throwable $exception) {
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

Artisan::command('mikrotik:sync-active-macs {--force : Poll every active router now, ignoring interval}', function (MikrotikCustomerSyncService $syncService) {
    $synced = 0;
    $failed = 0;

    MikrotikRouter::where('status', 'active')
        ->orderBy('id')
        ->get()
        ->each(function (MikrotikRouter $router) use ($syncService, &$synced, &$failed): void {
            $interval = max(1, (int) $router->active_mac_sync_interval_minutes);
            $isDue = $this->option('force')
                || ! $router->last_active_mac_sync_at
                || $router->last_active_mac_sync_at->addMinutes($interval)->lte(now());

            if (! $isDue) {
                $this->line("{$router->name} ({$router->ip_address}): active-MAC sync skipped until next interval.");

                return;
            }

            try {
                $summary = $syncService->syncActiveConnectionMacs($router);
                $text = "sessions={$summary['sessions']}, macs_updated={$summary['updated']}, unmatched={$summary['unmatched']}";

                $router->update([
                    'last_active_mac_sync_at' => now(),
                    'last_active_mac_sync_summary' => $text,
                ]);

                $synced++;
                $this->line("{$router->name} ({$router->ip_address}): {$text}");
            } catch (Throwable $exception) {
                $failed++;
                $router->update([
                    'last_active_mac_sync_at' => now(),
                    'last_active_mac_sync_summary' => 'failed: '.$exception->getMessage(),
                ]);
                $this->error("{$router->name} ({$router->ip_address}): failed - {$exception->getMessage()}");
            }
        });

    $this->info("Active-MAC sync finished. Synced: {$synced}. Failed: {$failed}.");

    return $failed === 0 ? self::SUCCESS : self::FAILURE;
})->purpose('Copy live /ppp/active device MACs onto matching parties by each router interval');

Artisan::command('mikrotik:import-secrets', function (MikrotikImportService $importService) {
    $summary = $importService->refreshActiveRouterSecrets();

    foreach ($summary['results'] as $result) {
        if (isset($result['error'])) {
            $this->warn("{$result['router']}: import failed - {$result['error']}");
        } else {
            $this->line("{$result['router']}: {$result['count']} secret(s) read.");
        }
    }

    $this->info("Router secret refresh finished. Read: {$summary['imported']}. Failed routers: {$summary['failed']}.");

    return $summary['failed'] === 0 ? self::SUCCESS : self::FAILURE;
})->purpose('Re-pull PPPoE secrets from active routers so the "router users not in app" list stays fresh');

Artisan::command('billing:disable-overdue-customers {--date= : Cutoff date, defaults to today} {--force : Run even outside the configured daily window}', function (MikrotikCustomerSyncService $syncService) {
    if (! $this->option('force') && ! BillingWindow::isOpenNow()) {
        $this->info('Skipped billing/service expiry disable outside the configured '.BillingWindow::label().' window. Use --force for an intentional manual run.');

        return self::SUCCESS;
    }

    $date = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();
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
            ->where('status', 'active')
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

    $expiredValidityCustomerIds = Customer::query()
        ->where('status', 'active')
        ->where('never_suspend', false)
        ->whereNotNull('service_valid_until')
        ->whereDate('service_valid_until', '<', $date)
        ->where(function ($query) use ($date) {
            $query->whereNull('grace_until')
                ->orWhereDate('grace_until', '<', $date);
        })
        ->pluck('id');

    $customerIds = $overdueCustomerIds
        ->merge($expiredGraceCustomerIds)
        ->merge($expiredValidityCustomerIds)
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
                } catch (Throwable $exception) {
                    $this->warn("{$customer->connection_id}: disabled locally, MikroTik sync failed - {$exception->getMessage()}");
                }

                $disabled++;
                $this->line("{$customer->connection_id}: disabled after billing or service expiry.");
            }
        });

    $this->info("Billing/service expiry disable finished. Disabled customers: {$disabled}.");

    return self::SUCCESS;
})->purpose('Disable non-special customers after billing or service expiry and sync MikroTik inactive profile');

// Runs every hour, but only inside the configurable daily window
// (default 12:00–17:00) so parties are never cut off at night.
Schedule::command('billing:disable-overdue-customers')
    ->hourlyAt(0)
    ->when(fn (): bool => BillingWindow::isOpenNow())
    ->withoutOverlapping();

Schedule::command('mikrotik:sync-router-users')
    ->hourly()
    ->withoutOverlapping();

// Dispatcher runs often; each router is still gated by its own
// active_mac_sync_interval_minutes inside the command.
Schedule::command('mikrotik:sync-active-macs')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('mikrotik:import-secrets')
    ->everyThreeHours()
    ->withoutOverlapping();
