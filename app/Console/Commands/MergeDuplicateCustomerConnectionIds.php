<?php

namespace App\Console\Commands;

use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MergeDuplicateCustomerConnectionIds extends Command
{
    protected $signature = 'customers:merge-duplicate-connection-ids {--dry-run : Show planned merges only and skip writes}';

    protected $description = 'Merge duplicate customer records that share the same normalized connection ID';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $duplicates = $this->findDuplicateConnectionIdGroups();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate connection IDs found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry-run mode is enabled. No data will be written.');
        }

        $foreignKeys = $this->customerForeignKeys();
        $mergedGroups = 0;
        $mergedCustomers = 0;
        $errors = 0;

        foreach ($duplicates as $group) {
            $members = $this->customersForNormalizedConnectionId($group->connection_id);
            if ($members->count() <= 1) {
                continue;
            }

            $winner = $this->selectPrimaryCustomer($members);
            $losers = $members->reject(fn (object $member): bool => (int) $member->id === (int) $winner->id)->values();

            $loserIds = $losers->pluck('id')->join(', ');
            $this->info("Merging connection_id '{$group->connection_id}' with customer #{$winner->id} as keeper (losers: {$loserIds})");

            foreach ($losers as $loser) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($winner, $loser, $foreignKeys, $dryRun): void {
                            $this->transferForeignReferences((int) $winner->id, (int) $loser->id, $foreignKeys, $dryRun);
                            $this->mergeWinnerIdentity((int) $winner->id, (object) $winner, (object) $loser);
                            $this->softDeleteDuplicate((int) $loser->id);
                        });
                    } else {
                        $this->previewTransfer((int) $winner->id, (int) $loser->id, $foreignKeys);
                    }

                    $mergedCustomers++;
                } catch (\Throwable $exception) {
                    $errors++;
                    $this->error("Failed to merge loser #{$loser->id} into keeper #{$winner->id}: {$exception->getMessage()}");
                }
            }

            $mergedGroups++;
        }

        if ($dryRun) {
            $this->info("Dry run complete. Found {$mergedGroups} duplicate groups and {$mergedCustomers} duplicate rows.");
            $this->line("Run without --dry-run to apply changes.");

            return self::SUCCESS;
        }

        if ($errors > 0) {
            $this->warn("Merge completed with {$errors} issue(s). Check earlier messages.");
            $this->info("Merged {$mergedGroups} duplicate groups and {$mergedCustomers} duplicate rows.");

            return self::FAILURE;
        }

        $this->info("Merged {$mergedGroups} duplicate groups and {$mergedCustomers} duplicate rows.");

        $remaining = $this->findDuplicateConnectionIdGroups();
        if ($remaining->isNotEmpty()) {
            $this->warn('Some connection_id values still have duplicates. Re-run command to continue or resolve conflicts manually.');
        } else {
            $this->info('No duplicate connection IDs remain.');
        }

        return self::SUCCESS;
    }

    private function mergeWinnerIdentity(int $winnerId, object $winner, object $loser): void
    {
        $currentWinner = (object) DB::table('customers')
            ->select(['mikrotik_username', 'fixed_ip_address', 'notes'])
            ->whereKey($winnerId)
            ->first();
        $winnerUpdates = [];

        if ((string) ($currentWinner->mikrotik_username ?? '') === '' && (string) $loser->mikrotik_username !== '') {
            $winnerUpdates['mikrotik_username'] = $loser->mikrotik_username;
        }

        if ((string) ($currentWinner->fixed_ip_address ?? '') === '' && (string) $loser->fixed_ip_address !== '') {
            $winnerUpdates['fixed_ip_address'] = $loser->fixed_ip_address;
        }

        $mergeNote = $this->mergeNoteForWinner((int) $winnerId, $loser);
        if ($mergeNote) {
            $notes = trim((string) ($currentWinner->notes ?? ''));
            $winnerUpdates['notes'] = $notes === '' ? $mergeNote : $notes.PHP_EOL.PHP_EOL.$mergeNote;
        }

        if (! empty($winnerUpdates)) {
            DB::table('customers')->whereKey($winnerId)->update($winnerUpdates);
        }
    }

    private function softDeleteDuplicate(int $customerId): void
    {
        $timestamp = now()->toDateTimeString();

        DB::table('customers')
            ->whereKey($customerId)
            ->update([
                'status' => 'inactive',
                'connection_id' => null,
                'mikrotik_username' => null,
                'fixed_ip_address' => null,
                'deleted_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
    }

    private function mergeNoteForWinner(int $winnerId, object $loser): ?string
    {
        if ($loser->deleted_at) {
            return "Merged inactive duplicate customer #{$loser->id} (deleted at {$loser->deleted_at}) into keeper customer #{$winnerId}.";
        }

        return "Merged duplicate customer #{$loser->id} into keeper customer #{$winnerId}.";
    }

    /**
     * @return Collection<int, object>
     */
    private function findDuplicateConnectionIdGroups(): Collection
    {
        if (DB::getDriverName() !== 'mysql') {
            return collect();
        }

        return collect(DB::select("
            SELECT LOWER(TRIM(connection_id)) AS connection_id, COUNT(*) AS total
            FROM customers
            WHERE connection_id IS NOT NULL
              AND TRIM(connection_id) <> ''
            GROUP BY LOWER(TRIM(connection_id))
            HAVING total > 1
            ORDER BY total DESC, connection_id ASC
        "));
    }

    /**
     * @return Collection<int, object>
     */
    private function customersForNormalizedConnectionId(string $normalizedConnectionId): Collection
    {
        return collect(DB::table('customers')
            ->select(['id', 'name', 'phone', 'connection_id', 'mikrotik_username', 'fixed_ip_address', 'notes', 'deleted_at', 'created_at'])
            ->whereNotNull('connection_id')
            ->whereRaw('LOWER(TRIM(connection_id)) = ?', [$normalizedConnectionId])
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get());
    }

    private function selectPrimaryCustomer(Collection $members): object
    {
        return $members->first(fn (object $member): bool => $member->deleted_at === null)
            ?: $members->first();
    }

    private function transferForeignReferences(int $winnerId, int $loserId, array $foreignKeys, bool $dryRun): void
    {
        foreach ($foreignKeys as $reference) {
            [$table, $column] = $reference;

            if ($table === 'customer_mikrotik_router' && $column === 'customer_id') {
                $this->moveMikrotikRouterLinks($winnerId, $loserId, $dryRun);
                continue;
            }

            $builder = DB::table($table)->where($column, $loserId);
            $count = (int) $builder->count();
            if ($count === 0) {
                continue;
            }

            if (! $dryRun) {
                $builder->update([$column => $winnerId]);
            }
        }
    }

    private function previewTransfer(int $winnerId, int $loserId, array $foreignKeys): void
    {
        foreach ($foreignKeys as $reference) {
            [$table, $column] = $reference;

            if ($table === 'customer_mikrotik_router' && $column === 'customer_id') {
                $count = (int) DB::table($table)->where($column, $loserId)->count();
                if ($count > 0) {
                    $this->line("  - {$table}.{$column}: {$count} row(s) will be moved to customer #{$winnerId}.");
                }

                continue;
            }

            $count = (int) DB::table($table)->where($column, $loserId)->count();
            if ($count > 0) {
                $this->line("  - {$table}.{$column}: {$count} row(s) will be reassigned to customer #{$winnerId}.");
            }
        }
    }

    private function moveMikrotikRouterLinks(int $winnerId, int $loserId, bool $dryRun): void
    {
        $rows = DB::table('customer_mikrotik_router')
            ->where('customer_id', $loserId)
            ->get(['mikrotik_router_id']);

        foreach ($rows as $row) {
            $exists = DB::table('customer_mikrotik_router')
                ->where('customer_id', $winnerId)
                ->where('mikrotik_router_id', $row->mikrotik_router_id)
                ->exists();

            if ($exists) {
                if (! $dryRun) {
                    DB::table('customer_mikrotik_router')
                        ->where('customer_id', $loserId)
                        ->where('mikrotik_router_id', $row->mikrotik_router_id)
                        ->delete();
                }

                continue;
            }

            if (! $dryRun) {
                DB::table('customer_mikrotik_router')
                    ->where('customer_id', $loserId)
                    ->where('mikrotik_router_id', $row->mikrotik_router_id)
                    ->update(['customer_id' => $winnerId]);
            }
        }
    }

    private function customerForeignKeys(): array
    {
        if (DB::getDriverName() === 'mysql') {
            return $this->mysqlCustomerForeignKeys();
        }

        return $this->sqliteFallbackForeignKeys();
    }

    private function mysqlCustomerForeignKeys(): array
    {
        $rows = DB::select("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME = 'customers'
              AND REFERENCED_COLUMN_NAME = 'id'
            ORDER BY TABLE_NAME, COLUMN_NAME
        ");

        $references = [];
        foreach ($rows as $row) {
            $key = "{$row->TABLE_NAME}|{$row->COLUMN_NAME}";
            if (! isset($references[$key])) {
                $references[$key] = [$row->TABLE_NAME, $row->COLUMN_NAME];
            }
        }

        return array_values($references);
    }

    /**
     * SQLite/other DB fallback for local/test environments.
     *
     * @return array<int, array<string>>
     */
    private function sqliteFallbackForeignKeys(): array
    {
        return [
            ['bkash_sms_payments', 'customer_id'],
            ['concession_logs', 'customer_id'],
            ['customers', 'reseller_id'],
            ['customer_balance_transactions', 'customer_id'],
            ['customer_mikrotik_router', 'customer_id'],
            ['customer_onu_power_samples', 'customer_id'],
            ['invoices', 'customer_id'],
            ['invoices', 'reseller_id'],
            ['mikrotik_imported_secrets', 'customer_id'],
            ['payments', 'customer_id'],
            ['payment_allocations', 'customer_id'],
            ['payment_allocations', 'funded_by_customer_id'],
            ['ppp_usage_logs', 'customer_id'],
            ['product_serials', 'customer_id'],
            ['purchase_bills', 'party_id'],
            ['quotations', 'customer_id'],
            ['reseller_commission_histories', 'reseller_id'],
            ['sale_returns', 'customer_id'],
            ['subscriptions', 'customer_id'],
            ['support_tickets', 'customer_id'],
            ['users', 'reseller_id'],
            ['warranty_claims', 'customer_id'],
            ['warranty_claims', 'vendor_id'],
        ];
    }
}
