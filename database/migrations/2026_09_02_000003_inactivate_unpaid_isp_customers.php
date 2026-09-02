<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $today = now()->toDateString();
        $timestamp = now();

        DB::table('customers')
            ->select('customers.id')
            ->whereNull('customers.deleted_at')
            ->where('customers.status', 'active')
            ->where('customers.never_suspend', false)
            ->whereNull('customers.service_valid_until')
            ->where(function ($query) use ($today): void {
                $query->whereNull('customers.grace_until')
                    ->orWhereDate('customers.grace_until', '<', $today);
            })
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.customer_id', 'customers.id');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('invoices')
                    ->whereColumn('invoices.customer_id', 'customers.id')
                    ->where('invoices.invoice_type', 'service')
                    ->where('invoices.due_amount', '<=', 0);
            })
            ->orderBy('customers.id')
            ->pluck('customers.id')
            ->chunk(500)
            ->each(function ($customerIds) use ($today, $timestamp): void {
                DB::table('customers')
                    ->whereIn('id', $customerIds->all())
                    ->update([
                        'status' => 'inactive',
                        'updated_at' => $timestamp,
                    ]);

                DB::table('subscriptions')
                    ->whereIn('customer_id', $customerIds->all())
                    ->where('status', 'active')
                    ->update([
                        'status' => 'inactive',
                        'end_date' => $today,
                        'updated_at' => $timestamp,
                    ]);
            });
    }

    public function down(): void
    {
        // Payment/grace history is required to reactivate service safely, so
        // the data correction is intentionally not reversed.
    }
};
