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
            $oldAdvanceBalance = (float) $customer->account_balance;
            $advanceRemaining = $oldAdvanceBalance;
            $paymentRemaining = (float) $data['amount'];
            $paymentDate = $data['payment_date'];

            $payment = Payment::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'note' => $data['note'] ?? null,
            ]);

            $dueInvoices = Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->orderByRaw('id = ? desc', [$invoice->id])
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($dueInvoices as $dueInvoice) {
                if ($advanceRemaining <= 0 && $paymentRemaining <= 0) {
                    break;
                }

                $dueBeforePayment = (float) $dueInvoice->due_amount;
                $advanceApplied = min($advanceRemaining, $dueBeforePayment);

                if ($advanceApplied > 0) {
                    PaymentAllocation::create([
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
                    'customer_id' => $customer->id,
                    'payment_id' => null,
                    'payment_account_id' => null,
                    'payment_method' => 'advance',
                    'direction' => 'debit',
                    'amount' => $advanceUsed,
                    'balance_after' => $advanceRemaining,
                    'transaction_date' => $paymentDate,
                    'reference' => 'INV-'.$invoice->id,
                    'note' => 'Advance balance applied to due invoice(s).',
                ]);
            }

            if ($paymentRemaining > 0) {
                CustomerBalanceTransaction::create([
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
}
