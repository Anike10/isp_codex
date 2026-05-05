<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        if ((float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if ((float) $data['amount'] > (float) $invoice->due_amount) {
            throw new InvalidArgumentException('Payment amount cannot be greater than due amount.');
        }

        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'note' => $data['note'] ?? null,
            ]);

            $invoice->paid_amount += $payment->amount;
            $invoice->due_amount = max(0, $invoice->total - $invoice->paid_amount);
            $invoice->status = $invoice->due_amount <= 0 ? 'paid' : 'partial';
            $invoice->save();

            return $payment;
        });
    }
}
