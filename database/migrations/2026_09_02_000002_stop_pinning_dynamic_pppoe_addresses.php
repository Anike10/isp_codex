<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dynamicCustomers = DB::table('customers')
            ->where('use_fixed_ip', false)
            ->get(['id', 'connection_id', 'mikrotik_username']);

        DB::table('customers')
            ->where('use_fixed_ip', false)
            ->update([
                'fixed_ip_address' => null,
                'learned_ip_address' => null,
                'learned_ip_package_id' => null,
            ]);

        if (Schema::hasTable('mikrotik_imported_secrets')) {
            $customerIds = $dynamicCustomers->pluck('id')->all();
            $usernames = $dynamicCustomers
                ->flatMap(fn ($customer) => [$customer->mikrotik_username, $customer->connection_id])
                ->map(fn ($username) => mb_strtolower(trim((string) $username)))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($customerIds !== [] || $usernames !== []) {
                DB::table('mikrotik_imported_secrets')
                    ->where(function ($query) use ($customerIds, $usernames): void {
                        if ($customerIds !== []) {
                            $query->whereIn('customer_id', $customerIds);
                        }
                        if ($usernames !== []) {
                            $customerIds === []
                                ? $query->whereIn(DB::raw('lower(name)'), $usernames)
                                : $query->orWhereIn(DB::raw('lower(name)'), $usernames);
                        }
                    })
                    ->update(['remote_address' => null]);
            }
        }

        $duplicate = DB::table('customers')
            ->where('use_fixed_ip', true)
            ->whereNotNull('fixed_ip_address')
            ->select('fixed_ip_address', DB::raw('COUNT(*) AS aggregate'))
            ->groupBy('fixed_ip_address')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException("Duplicate fixed IP {$duplicate->fixed_ip_address} must be resolved before migration.");
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->unique('fixed_ip_address', 'customers_fixed_ip_address_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_fixed_ip_address_unique');
        });
    }
};
