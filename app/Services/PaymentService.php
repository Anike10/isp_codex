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
            $customer = $invoice->customer;

            $payment = Payment::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'note' => $data['note'] ?? null,
            ]);

            $availableAmount = (float) $customer->account_balance + (float) $payment->amount;
            $dueInvoices = Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->orderByRaw('id = ? desc', [$invoice->id])
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($dueInvoices as $dueInvoice) {
                if ($availableAmount <= 0) {
                    break;
                }

                $paidAgainstInvoice = min($availableAmount, (float) $dueInvoice->due_amount);

                $dueInvoice->paid_amount += $paidAgainstInvoice;
                $dueInvoice->due_amount = max(0, (float) $dueInvoice->total - (float) $dueInvoice->paid_amount);
                $dueInvoice->status = $dueInvoice->due_amount <= 0 ? 'paid' : 'partial';
                $dueInvoice->save();

                $availableAmount -= $paidAgainstInvoice;
            }

            $customer->update(['account_balance' => max(0, $availableAmount)]);

            $remainingDue = Invoice::where('customer_id', $customer->id)->where('due_amount', '>', 0)->sum('due_amount');

            if ((float) $remainingDue <= 0) {
                $customer->update(['status' => 'active']);

                if ($customer->activeSubscription) {
                    $customer->activeSubscription->update([
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
            } catch (Throwable) {
                // Payment is already recorded; MikroTik can be synced again from customer update or the artisan command.
            }
        }

        return $payment;
    }
}
