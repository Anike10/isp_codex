<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
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

            $payment = Payment::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'note' => $data['note'] ?? null,
            ]);

            $dueBeforePayment = (float) $invoice->due_amount;
            $paidAgainstInvoice = min((float) $payment->amount, $dueBeforePayment);
            $extraAmount = max(0, (float) $payment->amount - $dueBeforePayment);

            $invoice->paid_amount += $paidAgainstInvoice;
            $invoice->due_amount = max(0, $invoice->total - $invoice->paid_amount);
            $invoice->status = $invoice->due_amount <= 0 ? 'paid' : 'partial';
            $invoice->save();

            if ($extraAmount > 0) {
                $invoice->customer->increment('account_balance', $extraAmount);
            }

            if ($invoice->due_amount <= 0) {
                $invoice->customer->update(['status' => 'active']);

                if ($invoice->customer->activeSubscription) {
                    $invoice->customer->activeSubscription->update([
                        'status' => 'active',
                        'end_date' => null,
                    ]);
                }
            }

            return $payment;
        });

        $payment->load('invoice.customer');

        if ((float) $payment->invoice->due_amount <= 0) {
            try {
                $this->mikrotikSyncService->sync($payment->invoice->customer);
            } catch (Throwable) {
                // Payment is already recorded; MikroTik can be synced again from customer update or the artisan command.
            }
        }

        return $payment;
    }
}
