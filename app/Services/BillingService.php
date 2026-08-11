<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(private readonly ResellerCommissionService $resellerCommissionService) {}

    public function generateMonthlyBills(string $billingMonth): Collection
    {
        $month = Carbon::createFromFormat('Y-m', $billingMonth)->startOfMonth();
        $createdInvoices = collect();

        Subscription::query()
            ->with(['customer', 'package'])
            ->where('status', 'active')
            ->whereHas('customer', fn ($query) => $query->where('status', 'active')->where('never_suspend', true))
            ->chunkById(100, function ($subscriptions) use ($month, $createdInvoices) {
                foreach ($subscriptions as $subscription) {
                    $invoice = $this->createServiceInvoice($subscription, $month);

                    if ($invoice->wasRecentlyCreated) {
                        $createdInvoices->push($invoice);
                    }
                }
            });

        return $createdInvoices;
    }

    public function generateCurrentServiceBillForCustomer(Customer $customer, ?string $billingMonth = null): ?Invoice
    {
        $customer->loadMissing(['activeSubscription.package', 'subscriptions.package']);

        // Keep the most recent package on an expired Party. A payment can then
        // create the renewal bill and restore that same MikroTik profile.
        $subscription = $customer->activeSubscription
            ?: $customer->subscriptions->sortByDesc('id')->first();

        if (! $subscription || ! $subscription->package) {
            return null;
        }

        $month = $billingMonth
            ? Carbon::createFromFormat('Y-m', $billingMonth)->startOfMonth()
            : now()->startOfMonth();

        return $this->createServiceInvoice($subscription, $month);
    }

    /**
     * Create the next unpaid renewal for the Party's remembered package.
     * If the current billing month is already paid, the next month is used.
     * When an available balance is supplied, do not create a future invoice
     * unless that balance can pay the invoice in full.
     */
    public function generateNextRenewalServiceBillForCustomer(
        Customer $customer,
        string $paymentDate,
        ?float $availableBalance = null
    ): ?Invoice
    {
        $customer->loadMissing(['activeSubscription.package', 'subscriptions.package']);
        $subscription = $customer->activeSubscription
            ?: $customer->subscriptions->sortByDesc('id')->first();

        if (! $subscription || ! $subscription->package) {
            return null;
        }

        $month = Carbon::parse($paymentDate)->startOfMonth();

        // A paid July bill means another July payment renews August, not the
        // already-settled July invoice.
        for ($attempt = 0; $attempt < 24; $attempt++) {
            $existing = Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('billing_month', $month->format('Y-m'))
                ->where('invoice_type', 'service')
                ->first();

            if (! $existing) {
                $commission = $this->resellerCommissionService->calculate(
                    $subscription->customer,
                    (float) $subscription->package->monthly_price
                );
                $requiredAmount = round((float) $commission['net_total'], 2);

                if ($availableBalance !== null && round($availableBalance, 2) < $requiredAmount) {
                    return null;
                }

                return $this->createServiceInvoice($subscription, $month, $commission);
            }

            if ((float) $existing->due_amount > 0) {
                return $existing;
            }

            $month->addMonthNoOverflow();
        }

        return null;
    }

    /**
     * Remove only the reseller commission discount before a wallet payment.
     * Cash/bank accounting is intentionally not involved in this operation.
     */
    public function makeInvoiceWithoutResellerCommission(Invoice $invoice, Customer $reseller): Invoice
    {
        return DB::transaction(function () use ($invoice, $reseller): Invoice {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ((int) $invoice->customer?->reseller_id !== (int) $reseller->id) {
                throw new \InvalidArgumentException('This invoice does not belong to a party assigned to the reseller.');
            }

            if ((float) $invoice->paid_amount > 0 || $invoice->allocations()->exists()) {
                throw new \InvalidArgumentException('Without Commission cannot be applied after an invoice has received a payment.');
            }

            $grossTotal = round((float) ($invoice->gross_total ?? $invoice->subtotal), 2);
            $invoice->update([
                'reseller_id' => $reseller->id,
                'reseller_commission_percent' => 0,
                'reseller_commission_amount' => 0,
                'gross_total' => $grossTotal,
                'discount' => 0,
                'discount_type' => 'amount',
                'discount_value' => 0,
                'total' => $grossTotal,
                'due_amount' => $grossTotal,
                'status' => 'unpaid',
            ]);

            return $invoice->refresh();
        });
    }

    private function createServiceInvoice(Subscription $subscription, Carbon $month, ?array $commission = null): Invoice
    {
        return DB::transaction(function () use ($subscription, $month, $commission) {
            $subscription->loadMissing(['customer', 'package']);
            $commission ??= $this->resellerCommissionService->calculate(
                $subscription->customer,
                (float) $subscription->package->monthly_price
            );

            return Invoice::firstOrCreate(
                [
                    'customer_id' => $subscription->customer_id,
                    'billing_month' => $month->format('Y-m'),
                    'invoice_type' => 'service',
                ],
                [
                    'invoice_no' => Invoice::generateInvoiceNo($subscription->customer_id, $month->format('Y-m')),
                    'subtotal' => $subscription->package->monthly_price,
                    'reseller_id' => $commission['reseller_id'],
                    'reseller_commission_percent' => $commission['percent'],
                    'reseller_commission_amount' => $commission['amount'],
                    'gross_total' => $commission['gross_total'],
                    'discount' => $commission['amount'],
                    'discount_type' => 'amount',
                    'discount_value' => 0,
                    'vat' => 0,
                    'total' => $commission['net_total'],
                    'paid_amount' => 0,
                    'due_amount' => $commission['net_total'],
                    'status' => 'unpaid',
                    'due_date' => $month->copy()->day(10),
                    'invoice_type' => 'service',
                ]
            );
        });
    }
}
