<?php

namespace App\Console\Commands;

use App\Models\MikrotikRouter;
use App\Services\MikrotikCustomerSyncService;
use Illuminate\Console\Command;
use Throwable;

class ReleaseDynamicPppAddresses extends Command
{
    protected $signature = 'mikrotik:release-dynamic-addresses
        {--router=* : Only these MikroTik router database IDs}
        {--force : Confirm the affected PPP sessions may be disconnected once}';

    protected $description = 'Clear PPP secret remote-address for dynamic parties and let profile pools assign a free IP';

    public function handle(MikrotikCustomerSyncService $syncService): int
    {
        if (! $this->option('force')) {
            $this->error('No changes made. Re-run with --force to allow affected sessions to reconnect.');

            return self::FAILURE;
        }

        $routerIds = collect($this->option('router'))->map(fn ($id) => (int) $id)->filter()->unique();
        $routers = MikrotikRouter::query()
            ->where('status', 'active')
            ->when($routerIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $routerIds))
            ->orderBy('id')
            ->get();

        if ($routers->isEmpty()) {
            $this->error('No matching active MikroTik router was found.');

            return self::FAILURE;
        }

        $failures = 0;
        foreach ($routers as $router) {
            if ($router->read_only) {
                $failures++;
                $this->warn("{$router->name}: skipped because the router is read-only.");

                continue;
            }

            try {
                $summary = $syncService->releaseDynamicAddresses($router);
                $failures += $summary['failed'];
                $this->line(
                    "{$router->name}: managed={$summary['managed']}, released={$summary['released']}, "
                    ."already_dynamic={$summary['already_dynamic']}, missing={$summary['missing']}, failed={$summary['failed']}"
                );

                foreach ($summary['messages'] as $message) {
                    $this->warn($message);
                }
            } catch (Throwable $exception) {
                $failures++;
                $this->error("{$router->name}: ".$exception->getMessage());
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
