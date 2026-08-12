<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdvanceRenewalService
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly PaymentService $paymentService,
        private readonly MikrotikCustomerSyncService $mikrotikSyncService,
    ) {}

    /**
     * Buy as many complete package months as the advance balance covers.
     * Existing unpaid invoices intentionally stop this flow; they must be
     * settled through the normal allocation workflow first.
     */
    public function renew(
        Customer $customer,
        string $paymentDate,
        int $maxRenewals = 24,
        ?string $note = null,
    ): int {
        $maxRenewals = max(1, min(24, $maxRenewals));
        $note = trim((string) ($note ?: 'Renewal from customer advance balance.'));

        $renewedMonths = DB::transaction(function () use ($customer, $paymentDate, $maxRenewals, $note): int {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $renewedMonths = 0;

            for ($step = 0; $step < $maxRenewals; $step++) {
                $customer->refresh();

                if ((float) Invoice::query()
                    ->where('customer_id', $customer->id)
                    ->where('due_amount', '>', 0)
                    ->sum('due_amount') > 0) {
                    break;
                }

                $availableBalance = round((float) $customer->account_balance, 2);
                $renewalInvoice = $this->billingService->generateNextRenewalServiceBillForCustomer(
                    $customer,
                    $paymentDate,
                    $availableBalance,
                );

                if (! $renewalInvoice || (float) $renewalInvoice->due_amount <= 0) {
                    break;
                }

                if (round((float) $customer->refresh()->account_balance, 2) < round((float) $renewalInvoice->due_amount, 2)) {
                    break;
                }

                $this->paymentService->applyAdvanceToInvoice($customer->refresh(), $renewalInvoice, [
                    'amount' => $renewalInvoice->due_amount,
                    'payment_date' => $paymentDate,
                    'note' => $note,
                ], true);

                $renewedMonths++;
            }

            if ($renewedMonths <= 0) {
                return 0;
            }

            $customer = $customer->refresh();
            $this->paymentService->extendPaidServiceValidityFromCurrent(
                $customer,
                $paymentDate,
                $renewedMonths,
                $note,
            );

            $subscription = $customer->activeSubscription ?: $customer->subscriptions()->latest()->first();
            $subscription?->update([
                'status' => 'active',
                'end_date' => null,
            ]);

            return $renewedMonths;
        });

        if ($renewedMonths > 0) {
            try {
                $this->mikrotikSyncService->sync($customer->refresh());
            } catch (Throwable $exception) {
                Log::warning('MikroTik sync failed after advance renewal.', [
                    'customer_id' => $customer->id,
                    'renewed_months' => $renewedMonths,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $renewedMonths;
    }
}
