<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function generateMonthlyBills(string $billingMonth): Collection
    {
        $month = Carbon::createFromFormat('Y-m', $billingMonth)->startOfMonth();
        $createdInvoices = collect();

        Subscription::query()
            ->with(['customer', 'package'])
            ->where('status', 'active')
            ->whereHas('customer', fn ($query) => $query->where('status', 'active'))
            ->chunkById(100, function ($subscriptions) use ($month, $createdInvoices) {
                foreach ($subscriptions as $subscription) {
                    $invoice = DB::transaction(function () use ($subscription, $month) {
                        return Invoice::firstOrCreate(
                            [
                                'customer_id' => $subscription->customer_id,
                                'billing_month' => $month->format('Y-m'),
                                'invoice_type' => 'service',
                            ],
                            [
                                'invoice_no' => Invoice::generateInvoiceNo($subscription->customer_id, $month->format('Y-m')),
                                'subtotal' => $subscription->package->monthly_price,
                                'discount' => 0,
                                'vat' => 0,
                                'total' => $subscription->package->monthly_price,
                                'paid_amount' => 0,
                                'due_amount' => $subscription->package->monthly_price,
                                'status' => 'unpaid',
                                'due_date' => $month->copy()->day(10),
                                'invoice_type' => 'service',
                            ]
                        );
                    });

                    if ($invoice->wasRecentlyCreated) {
                        $createdInvoices->push($invoice);
                    }
                }
            });

        return $createdInvoices;
    }

}
