<?php

namespace App\Services;

use App\Models\BkashSmsPayment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BkashSmsPaymentService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly BillingService $billingService,
    )
    {
    }

    public function handle(string $rawSms, ?string $smsSender = null): BkashSmsPayment
    {
        $parsed = $this->parse($rawSms);

        if (! $parsed['trx_id'] || ! $parsed['amount']) {
            return BkashSmsPayment::create([
                'sms_sender' => $smsSender,
                'raw_sms' => $rawSms,
                'customer_number' => $parsed['customer_number'],
                'trx_id' => $parsed['trx_id'],
                'reference' => $parsed['reference'],
                'amount' => $parsed['amount'],
                'payment_date' => $parsed['payment_date'],
                'status' => 'failed',
                'message' => 'Could not parse bKash amount or TrxID from SMS.',
            ]);
        }

        $existing = BkashSmsPayment::where('trx_id', $parsed['trx_id'])->first();

        if ($existing) {
            return BkashSmsPayment::create([
                'sms_sender' => $smsSender,
                'raw_sms' => $rawSms,
                'customer_number' => $parsed['customer_number'],
                'trx_id' => $parsed['trx_id'],
                'reference' => $parsed['reference'],
                'amount' => $parsed['amount'],
                'payment_date' => $parsed['payment_date'],
                'status' => 'duplicate',
                'customer_id' => $existing->customer_id,
                'invoice_id' => $existing->invoice_id,
                'message' => 'Duplicate TrxID. Ledger was not updated.',
            ]);
        }

        return DB::transaction(function () use ($rawSms, $smsSender, $parsed): BkashSmsPayment {
            try {
                $smsPayment = BkashSmsPayment::create([
                    'sms_sender' => $smsSender,
                    'raw_sms' => $rawSms,
                    'customer_number' => $parsed['customer_number'],
                    'trx_id' => $parsed['trx_id'],
                    'ledger_trx_id' => $parsed['trx_id'],
                    'reference' => $parsed['reference'],
                    'amount' => $parsed['amount'],
                    'payment_date' => $parsed['payment_date'],
                    'status' => 'pending',
                ]);
            } catch (QueryException) {
                $existing = BkashSmsPayment::where('ledger_trx_id', $parsed['trx_id'])->first();

                return BkashSmsPayment::create([
                    'sms_sender' => $smsSender,
                    'raw_sms' => $rawSms,
                    'customer_number' => $parsed['customer_number'],
                    'trx_id' => $parsed['trx_id'],
                    'reference' => $parsed['reference'],
                    'amount' => $parsed['amount'],
                    'payment_date' => $parsed['payment_date'],
                    'status' => 'duplicate',
                    'customer_id' => $existing?->customer_id,
                    'invoice_id' => $existing?->invoice_id,
                    'message' => 'Duplicate TrxID. Ledger was not updated.',
                ]);
            }

            [$customer, $matchMessage] = $this->findCustomer($parsed['reference'], $parsed['customer_number']);

            if (! $customer) {
                $smsPayment->update([
                    'status' => 'pending',
                    'message' => $matchMessage,
                ]);

                return $smsPayment;
            }

            if (! $customer->never_suspend) {
                $this->billingService->generateCurrentServiceBillForCustomer($customer);
            }

            $invoice = Invoice::where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->orderBy('due_date')
                ->orderBy('id')
                ->first();

            if (! $invoice) {
                $customer->increment('account_balance', $parsed['amount']);

                $smsPayment->update([
                    'status' => 'balance',
                    'customer_id' => $customer->id,
                    'message' => $matchMessage.' No due invoice found. Amount added to customer account balance.',
                ]);

                return $smsPayment;
            }

            try {
                $paymentAccount = $parsed['customer_number']
                    ? PaymentAccount::firstOrCreate(
                        [
                            'payment_method' => 'bkash',
                            'account_number' => $parsed['customer_number'],
                        ],
                        [
                            'account_name' => 'bKash Sender '.$parsed['customer_number'],
                            'opening_balance' => 0,
                            'status' => 'active',
                        ]
                    )
                    : null;

                $payment = $this->paymentService->recordPayment($invoice, [
                    'amount' => $parsed['amount'],
                    'payment_method' => 'bkash',
                    'payment_account_id' => $paymentAccount?->id,
                    'payment_date' => $parsed['payment_date']?->toDateString() ?? now()->toDateString(),
                    'note' => 'Auto bKash SMS TrxID: '.$parsed['trx_id'],
                ]);
            } catch (InvalidArgumentException $exception) {
                $smsPayment->update([
                    'status' => 'failed',
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'message' => $exception->getMessage(),
                ]);

                return $smsPayment;
            }

            $smsPayment->update([
                'status' => 'processed',
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'message' => $matchMessage.' Payment recorded successfully.',
            ]);

            return $smsPayment;
        });
    }

    public function parse(string $rawSms): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($rawSms));

        preg_match('/(?:Tk|BDT)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $normalized, $amountMatch);
        preg_match('/(?:TrxID|Trx ID|TxnID|Transaction ID)[:\s]*([A-Z0-9]+)/i', $normalized, $trxMatch);
        preg_match('/(?:Ref|Reference)[:\s]*([^\.\s]+)/i', $normalized, $referenceMatch);
        preg_match('/(?:from|Frm|Sender)[:\s]*(\+?88)?(01[0-9]{9})/i', $normalized, $numberMatch);

        $paymentDate = null;

        if (preg_match('/(?:at|on)\s+([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4})\s+([0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?\s*(?:AM|PM)?)/i', $normalized, $dateMatch)) {
            try {
                $paymentDate = Carbon::parse($dateMatch[1].' '.$dateMatch[2]);
            } catch (\Throwable) {
                $paymentDate = null;
            }
        }

        return [
            'amount' => isset($amountMatch[1]) ? (float) str_replace(',', '', $amountMatch[1]) : null,
            'trx_id' => isset($trxMatch[1]) ? strtoupper($trxMatch[1]) : null,
            'reference' => $referenceMatch[1] ?? null,
            'customer_number' => isset($numberMatch[2]) ? $this->normalizePhone($numberMatch[2]) : null,
            'payment_date' => $paymentDate,
        ];
    }

    private function findCustomer(?string $reference, ?string $phone): array
    {
        if ($reference) {
            $referenceCustomer = Customer::query()
                ->where('mikrotik_username', $reference)
                ->orWhere('connection_id', $reference)
                ->first();

            if ($referenceCustomer) {
                return [$referenceCustomer, 'Customer matched by reference user ID.'];
            }
        }

        if (! $phone) {
            return [null, $reference ? 'Reference user ID did not match and no sender number was parsed.' : 'No reference or sender number found.'];
        }

        $matches = Customer::query()
            ->get()
            ->filter(fn (Customer $customer) => $this->normalizePhone($customer->phone) === $phone)
            ->values();

        if ($matches->count() === 1) {
            return [$matches->first(), $reference ? 'Reference user ID did not match. Customer matched by sender number.' : 'Customer matched by sender number.'];
        }

        if ($matches->count() > 1) {
            return [null, 'Multiple customers matched this bKash sender number. Manual review required.'];
        }

        return [null, $reference ? 'Reference user ID did not match and no customer matched this bKash sender number.' : 'No customer matched this bKash sender number.'];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '8801') && strlen($digits) === 13) {
            return '0'.substr($digits, 3);
        }

        if (str_starts_with($digits, '1') && strlen($digits) === 10) {
            return '0'.$digits;
        }

        return $digits;
    }
}
