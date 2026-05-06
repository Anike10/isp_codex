<?php

use App\Models\Customer;
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
