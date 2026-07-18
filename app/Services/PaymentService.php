<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PaymentService
{
    public function __construct(private readonly MikrotikCustomerSyncService $mikrotikSyncService) {}

    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        if ((float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $payment = DB::transaction(function () use ($invoice, $data) {
            $invoice->load('customer.activeSubscription');
            $customer = Customer::whereKey($invoice->customer_id)->lockForUpdate()->firstOrFail();
            $targetInvoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ((float) $targetInvoice->due_amount <= 0) {
                throw new InvalidArgumentException('This invoice is already paid.');
            }

            $oldAdvanceBalance = $this->money($customer->account_balance);
            $advanceRemaining = $oldAdvanceBalance;
            $paymentRemaining = $this->money($data['amount']);
            $paymentDate = $data['payment_date'];

            $payment = Payment::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'amount' => $paymentRemaining,
                'payment_method' => $data['payment_method'],
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'note' => $data['note'] ?? null,
            ]);

            $dueInvoices = collect([$targetInvoice])->merge(Invoice::query()
                ->where('customer_id', $customer->id)
                ->whereKeyNot($targetInvoice->id)
                ->where('due_amount', '>', 0)
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get());

            foreach ($dueInvoices as $dueInvoice) {
                if ($advanceRemaining <= 0 && $paymentRemaining <= 0) {
                    break;
                }

                $dueBeforePayment = $this->money($dueInvoice->due_amount);
                $advanceApplied = $this->money(min($advanceRemaining, $dueBeforePayment));

                if ($advanceApplied > 0) {
                    PaymentAllocation::create([
                        'entry_by' => $data['entry_by'] ?? null,
                        'customer_id' => $customer->id,
                        'invoice_id' => $dueInvoice->id,
                        'payment_id' => null,
                        'source_type' => 'advance',
                        'amount' => $advanceApplied,
                        'allocated_at' => $paymentDate,
                        'note' => 'Applied from customer advance balance.',
                    ]);

                    $advanceRemaining = $this->money($advanceRemaining - $advanceApplied);
                    $dueBeforePayment = $this->money($dueBeforePayment - $advanceApplied);
                }

                $paymentApplied = $this->money(min($paymentRemaining, $dueBeforePayment));

                if ($paymentApplied > 0) {
                    PaymentAllocation::create([
                        'entry_by' => $data['entry_by'] ?? null,
                        'customer_id' => $customer->id,
                        'invoice_id' => $dueInvoice->id,
                        'payment_id' => $payment->id,
                        'source_type' => 'payment',
                        'amount' => $paymentApplied,
                        'allocated_at' => $paymentDate,
                        'note' => $data['note'] ?? null,
                    ]);

                    $paymentRemaining = $this->money($paymentRemaining - $paymentApplied);
                }

                $paidAgainstInvoice = $this->money($advanceApplied + $paymentApplied);

                if ($paidAgainstInvoice <= 0) {
                    continue;
                }

                $dueInvoice->paid_amount = $this->money((float) $dueInvoice->paid_amount + $paidAgainstInvoice);
                $dueInvoice->recalculateSettlement();
            }

            $advanceUsed = $this->money($oldAdvanceBalance - $advanceRemaining);
            $newAdvanceBalance = $this->money($advanceRemaining + $paymentRemaining);

            if ($advanceUsed > 0) {
                CustomerBalanceTransaction::create([
                    'entry_by' => $data['entry_by'] ?? null,
                    'customer_id' => $customer->id,
                    'payment_id' => null,
                    'payment_account_id' => null,
                    'payment_method' => 'advance',
                    'direction' => 'debit',
                    'amount' => $advanceUsed,
                    'balance_after' => $advanceRemaining,
                    'transaction_date' => $paymentDate,
                    'reference' => 'PAY-'.$payment->id,
                    'note' => 'Advance balance applied to due invoice(s).',
                ]);
            }

            if ($paymentRemaining > 0) {
                CustomerBalanceTransaction::create([
                    'entry_by' => $data['entry_by'] ?? null,
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'payment_account_id' => $data['payment_account_id'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'direction' => 'credit',
                    'amount' => $paymentRemaining,
                    'balance_after' => $newAdvanceBalance,
                    'transaction_date' => $paymentDate,
                    'reference' => $data['reference'] ?? null,
                    'note' => 'Unallocated payment amount added to customer advance balance.',
                ]);
            }

            $customer->update(['account_balance' => max(0, $newAdvanceBalance)]);

            $remainingDue = Invoice::where('customer_id', $customer->id)->where('due_amount', '>', 0)->sum('due_amount');

            if ((float) $remainingDue <= 0) {
                $this->activatePaidServiceValidity($customer, $paymentDate, $data['note'] ?? null);

                $subscription = $customer->activeSubscription ?: $customer->subscriptions()->latest()->first();

                if ($subscription) {
                    $subscription->update([
                        'status' => 'active',
                        'end_date' => null,
                    ]);
                }
            }

            return $payment;
        });

        $payment->load('invoice.customer');

        if ((float) Invoice::where('customer_id', $payment->customer_id)->where('due_amount', '>', 0)->sum('due_amount') <= 0) {
            try {
                $this->mikrotikSyncService->sync($payment->invoice->customer);
            } catch (Throwable $exception) {
                Log::warning('MikroTik sync failed after payment activation.', [
                    'payment_id' => $payment->id,
                    'customer_id' => $payment->customer_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $payment;
    }

    public function recordPaymentForInvoices(Customer $customer, array $invoiceIds, array $data): Payment
    {
        if ((float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $payment = DB::transaction(function () use ($customer, $invoiceIds, $data) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $invoices = Invoice::query()
                ->where('customer_id', $customer->id)
                ->whereIn('id', $invoiceIds)
                ->where('due_amount', '>', 0)
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($invoices->count() !== count(array_unique($invoiceIds))) {
                throw new InvalidArgumentException('Selected invoice list contains paid invoices or invoices from another party.');
            }

            $selectedDue = round((float) $invoices->sum('due_amount'), 2);
            $paymentAmount = round((float) $data['amount'], 2);

            if ($paymentAmount !== $selectedDue) {
                throw new InvalidArgumentException('Payment amount must match the selected invoice due total.');
            }

            $firstInvoice = $invoices->first();

            $payment = Payment::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $customer->id,
                'invoice_id' => $firstInvoice->id,
                'amount' => $paymentAmount,
                'payment_method' => $data['payment_method'],
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'note' => $data['note'] ?? null,
            ]);

            foreach ($invoices as $invoice) {
                $amount = (float) $invoice->due_amount;

                PaymentAllocation::create([
                    'entry_by' => $data['entry_by'] ?? null,
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'source_type' => 'payment',
                    'amount' => $amount,
                    'allocated_at' => $data['payment_date'],
                    'note' => $data['note'] ?? 'Bulk payment from selected invoices.',
                ]);

                $invoice->paid_amount = $this->money((float) $invoice->paid_amount + $amount);
                $invoice->recalculateSettlement();
            }

            $remainingDue = Invoice::where('customer_id', $customer->id)->where('due_amount', '>', 0)->sum('due_amount');

            if ((float) $remainingDue <= 0) {
                $this->activatePaidServiceValidity($customer, $data['payment_date'], $data['note'] ?? null);

                $subscription = $customer->activeSubscription ?: $customer->subscriptions()->latest()->first();

                if ($subscription) {
                    $subscription->update([
                        'status' => 'active',
                        'end_date' => null,
                    ]);
                }
            }

            return $payment;
        });

        $payment->load('customer');

        if ((float) Invoice::where('customer_id', $payment->customer_id)->where('due_amount', '>', 0)->sum('due_amount') <= 0) {
            try {
                $this->mikrotikSyncService->sync($payment->customer);
            } catch (Throwable $exception) {
                Log::warning('MikroTik sync failed after selected invoice payment activation.', [
                    'payment_id' => $payment->id,
                    'customer_id' => $payment->customer_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $payment;
    }

    public function addAdvanceCredit(Customer $customer, array $data): CustomerBalanceTransaction
    {
        if ((float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('Advance amount must be greater than zero.');
        }

        return DB::transaction(function () use ($customer, $data) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $amount = $this->money($data['amount']);
            $balanceAfter = $this->money((float) $customer->account_balance + $amount);

            $transaction = CustomerBalanceTransaction::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $customer->id,
                'payment_id' => null,
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'transaction_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $customer->update(['account_balance' => $balanceAfter]);

            return $transaction;
        });
    }

    public function applyResellerWalletToInvoice(Customer $reseller, Invoice $invoice, array $data): PaymentAllocation
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $operationKey = (string) ($data['operation_key'] ?? '');
        $paymentDate = (string) ($data['payment_date'] ?? now()->toDateString());

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if ($operationKey === '') {
            throw new InvalidArgumentException('A unique payment operation key is required.');
        }

        $allocation = DB::transaction(function () use ($reseller, $invoice, $data, $amount, $operationKey, $paymentDate) {
            $reseller = Customer::query()->whereKey($reseller->id)->lockForUpdate()->firstOrFail();

            $existing = PaymentAllocation::query()->where('operation_key', $operationKey)->first();
            if ($existing) {
                if ((int) $existing->funded_by_customer_id !== (int) $reseller->id) {
                    throw new InvalidArgumentException('This payment operation key has already been used.');
                }

                return $existing;
            }

            $invoice = Invoice::query()->with('customer')->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $customer = $invoice->customer;

            if (! $reseller->is_reseller || $reseller->status !== 'active') {
                throw new InvalidArgumentException('The reseller wallet is not active.');
            }

            if ((int) $customer->reseller_id !== (int) $reseller->id) {
                throw new InvalidArgumentException('This customer is not assigned to the reseller.');
            }

            if ((float) $invoice->due_amount <= 0) {
                throw new InvalidArgumentException('This invoice is already paid.');
            }

            if ($amount > (float) $invoice->due_amount) {
                throw new InvalidArgumentException('Payment cannot be greater than the invoice due amount.');
            }

            if ($amount > (float) $reseller->account_balance) {
                throw new InvalidArgumentException('The reseller wallet has insufficient balance.');
            }

            $dailyLimit = $reseller->reseller_daily_payment_limit !== null
                ? (float) $reseller->reseller_daily_payment_limit
                : null;
            $spentToday = (float) CustomerBalanceTransaction::query()
                ->where('customer_id', $reseller->id)
                ->where('payment_method', 'reseller_wallet')
                ->where('direction', 'debit')
                ->whereDate('transaction_date', $paymentDate)
                ->sum('amount');

            if ($dailyLimit !== null && round($spentToday + $amount, 2) > $dailyLimit) {
                $remaining = max(0, $dailyLimit - $spentToday);
                throw new InvalidArgumentException('Daily reseller payment limit exceeded. Remaining today: '.number_format($remaining, 2).'.');
            }

            $allocation = PaymentAllocation::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $customer->id,
                'funded_by_customer_id' => $reseller->id,
                'invoice_id' => $invoice->id,
                'payment_id' => null,
                'source_type' => 'reseller_wallet',
                'operation_key' => $operationKey,
                'amount' => $amount,
                'allocated_at' => $paymentDate,
                'note' => $data['note'] ?? 'Paid from reseller wallet.',
            ]);

            $invoice->paid_amount = $this->money((float) $invoice->paid_amount + $amount);
            $invoice->recalculateSettlement();

            $balanceAfter = round((float) $reseller->account_balance - $amount, 2);
            CustomerBalanceTransaction::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $reseller->id,
                'payment_id' => null,
                'invoice_id' => $invoice->id,
                'payment_account_id' => null,
                'payment_method' => 'reseller_wallet',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'transaction_date' => $paymentDate,
                'reference' => 'INV-'.$invoice->id,
                'operation_key' => $operationKey,
                'note' => $data['note'] ?? 'Invoice paid from reseller wallet for '.$customer->name.'.',
            ]);
            $reseller->update(['account_balance' => $balanceAfter]);

            return $allocation;
        });

        if (! $allocation->wasRecentlyCreated) {
            return $allocation;
        }

        $invoice = Invoice::query()->with('customer')->findOrFail($allocation->invoice_id);
        $customer = $invoice->customer;

        if ((float) Invoice::query()->where('customer_id', $customer->id)->where('due_amount', '>', 0)->sum('due_amount') <= 0) {
            $this->activatePaidServiceValidity($customer, $paymentDate, $data['note'] ?? 'Paid from reseller wallet.');
            $subscription = $customer->activeSubscription ?: $customer->subscriptions()->latest()->first();

            if ($subscription) {
                $subscription->update(['status' => 'active', 'end_date' => null]);
            }

            try {
                $this->mikrotikSyncService->sync($customer->refresh());
            } catch (Throwable $exception) {
                Log::warning('MikroTik sync failed after reseller wallet payment.', [
                    'reseller_id' => $reseller->id,
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $allocation;
    }

    public function applyAdvanceToInvoice(Customer $customer, Invoice $invoice, array $data): PaymentAllocation
    {
        if ((float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('Applied amount must be greater than zero.');
        }

        $allocation = DB::transaction(function () use ($customer, $invoice, $data) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ((int) $invoice->customer_id !== (int) $customer->id) {
                throw new InvalidArgumentException('This invoice does not belong to the selected customer.');
            }

            $amount = $this->money($data['amount']);
            $currentBalance = $this->money($customer->account_balance);
            $currentDue = $this->money($invoice->due_amount);

            if ($currentBalance <= 0) {
                throw new InvalidArgumentException('This customer has no advance balance to apply.');
            }

            if ($currentDue <= 0) {
                throw new InvalidArgumentException('This invoice is already paid.');
            }

            if ($amount > $currentBalance) {
                throw new InvalidArgumentException('Applied amount cannot be greater than the customer advance balance.');
            }

            if ($amount > $currentDue) {
                throw new InvalidArgumentException('Applied amount cannot be greater than the invoice due amount.');
            }

            $allocation = PaymentAllocation::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'payment_id' => null,
                'source_type' => 'advance',
                'amount' => $amount,
                'allocated_at' => $data['payment_date'] ?? now()->toDateString(),
                'note' => $data['note'] ?? 'Applied from customer advance balance.',
            ]);

            $invoice->paid_amount = $this->money((float) $invoice->paid_amount + $amount);
            $invoice->recalculateSettlement();

            $balanceAfter = $this->money(max(0, $currentBalance - $amount));

            CustomerBalanceTransaction::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $customer->id,
                'payment_id' => null,
                'payment_account_id' => null,
                'payment_method' => 'advance',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'transaction_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference' => 'INV-'.$invoice->id,
                'note' => $data['note'] ?? 'Advance balance applied to invoice.',
            ]);

            $customer->update(['account_balance' => $balanceAfter]);

            return $allocation;
        });

        $customer->refresh();

        if ((float) Invoice::where('customer_id', $customer->id)->where('due_amount', '>', 0)->sum('due_amount') <= 0) {
            $this->activatePaidServiceValidity($customer, $data['payment_date'] ?? now()->toDateString(), $data['note'] ?? null);

            $subscription = $customer->activeSubscription ?: $customer->subscriptions()->latest()->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'active',
                    'end_date' => null,
                ]);
            }

            try {
                $this->mikrotikSyncService->sync($customer->refresh());
            } catch (Throwable $exception) {
                Log::warning('MikroTik sync failed after applying advance balance.', [
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $allocation;
    }

    public function addAdvanceCreditAndApplyToInvoices(Customer $customer, array $data, array $invoiceAmounts): CustomerBalanceTransaction
    {
        return DB::transaction(function () use ($customer, $data, $invoiceAmounts) {
            $transaction = $this->addAdvanceCredit($customer, $data);

            foreach ($invoiceAmounts as $invoiceId => $amount) {
                $amount = (float) $amount;

                if ($amount <= 0) {
                    continue;
                }

                $invoice = Invoice::where('customer_id', $customer->id)->findOrFail($invoiceId);

                $this->applyAdvanceToInvoice($customer->refresh(), $invoice, [
                    'amount' => $amount,
                    'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                    'note' => $data['note'] ?? 'Applied from advance payment entry.',
                ]);
            }

            return $transaction;
        });
    }

    /** Set a fresh paid month, less any grace days already consumed for this renewal. */
    private function activatePaidServiceValidity(Customer $customer, string $paymentDate, ?string $paymentNote = null): void
    {
        $startsOn = Carbon::parse($paymentDate)->startOfDay();
        $monthDays = $startsOn->copy()->diffInDays($startsOn->copy()->addMonthNoOverflow());
        $graceDays = min(max(0, (int) ($customer->grace_days ?? 0)), max(0, $monthDays - 1));
        $validDays = $monthDays - $graceDays;
        $validUntil = $startsOn->copy()->addDays($validDays - 1);
        $detail = sprintf(
            '[%s] Paid validity: payment date %s; one-month period %d day(s); grace deducted %d day(s); validity %d day(s), %s to %s.%s',
            now()->format('Y-m-d H:i'),
            $startsOn->format('Y-m-d'),
            $monthDays,
            $graceDays,
            $validDays,
            $startsOn->format('Y-m-d'),
            $validUntil->format('Y-m-d'),
            $paymentNote ? ' Payment note: '.trim($paymentNote) : ''
        );

        $customer->update([
            'status' => 'active',
            'service_valid_from' => $startsOn->toDateString(),
            'service_valid_until' => $validUntil->toDateString(),
            'service_validity_note' => $detail,
            'grace_until' => null,
            'grace_days' => null,
            'grace_used_at' => null,
            'notes' => trim(implode("\n", array_filter([$customer->notes, $detail]))),
        ]);
    }

    private function money(mixed $amount): float
    {
        return round((float) $amount, 2);
    }
}
