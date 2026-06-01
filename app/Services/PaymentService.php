<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PaymentService
{
    public function __construct(private readonly MikrotikCustomerSyncService $mikrotikSyncService)
    {
    }

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

            $oldAdvanceBalance = (float) $customer->account_balance;
            $advanceRemaining = $oldAdvanceBalance;
            $paymentRemaining = (float) $data['amount'];
            $paymentDate = $data['payment_date'];

            $payment = Payment::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
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

                $dueBeforePayment = (float) $dueInvoice->due_amount;
                $advanceApplied = min($advanceRemaining, $dueBeforePayment);

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

                    $advanceRemaining -= $advanceApplied;
                    $dueBeforePayment -= $advanceApplied;
                }

                $paymentApplied = min($paymentRemaining, $dueBeforePayment);

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

                    $paymentRemaining -= $paymentApplied;
                }

                $paidAgainstInvoice = $advanceApplied + $paymentApplied;

                if ($paidAgainstInvoice <= 0) {
                    continue;
                }

                $dueInvoice->paid_amount += $paidAgainstInvoice;
                $dueInvoice->due_amount = max(0, (float) $dueInvoice->total - (float) $dueInvoice->paid_amount);
                $dueInvoice->status = $dueInvoice->due_amount <= 0 ? 'paid' : 'partial';
                $dueInvoice->save();
            }

            $advanceUsed = $oldAdvanceBalance - $advanceRemaining;
            $newAdvanceBalance = $advanceRemaining + $paymentRemaining;

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
                $customer->update(['status' => 'active']);

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

    public function addAdvanceCredit(Customer $customer, array $data): CustomerBalanceTransaction
    {
        if ((float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('Advance amount must be greater than zero.');
        }

        return DB::transaction(function () use ($customer, $data) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $balanceAfter = (float) $customer->account_balance + (float) $data['amount'];

            $transaction = CustomerBalanceTransaction::create([
                'entry_by' => $data['entry_by'] ?? null,
                'customer_id' => $customer->id,
                'payment_id' => null,
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'direction' => 'credit',
                'amount' => $data['amount'],
                'balance_after' => $balanceAfter,
                'transaction_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $customer->update(['account_balance' => $balanceAfter]);

            return $transaction;
        });
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

            $amount = (float) $data['amount'];
            $currentBalance = (float) $customer->account_balance;
            $currentDue = (float) $invoice->due_amount;

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

            $invoice->paid_amount += $amount;
            $invoice->due_amount = max(0, (float) $invoice->total - (float) $invoice->paid_amount);
            $invoice->status = $invoice->due_amount <= 0 ? 'paid' : 'partial';
            $invoice->save();

            $balanceAfter = max(0, $currentBalance - $amount);

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
            $customer->update(['status' => 'active']);

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
}
