<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class BulkCustomerPaymentService
{
    public function __construct(private readonly MikrotikCustomerSyncService $mikrotikSyncService) {}

    /** @return array<string, string> */
    public function durationOptions(): array
    {
        return [
            'month_1' => '1 Month',
            'day_1' => '1 Day',
            'day_7' => '7 Days',
            'day_14' => '14 Days',
        ];
    }

    public function amountForPrice(float $monthlyPrice, string $duration): float
    {
        return match ($duration) {
            'month_1' => round($monthlyPrice, 2),
            'day_1' => round($monthlyPrice / 30, 2),
            'day_7' => round(($monthlyPrice / 30) * 7, 2),
            'day_14' => round(($monthlyPrice / 30) * 14, 2),
            default => throw new InvalidArgumentException('Invalid bulk payment duration.'),
        };
    }

    /** @return array{count: int, total: float, sync_failures: int} */
    public function record(array $customerIds, array $data, ?int $entryBy, string $batchToken): array
    {
        $duration = (string) $data['duration'];
        $paymentDate = Carbon::parse($data['payment_date'])->startOfDay();
        $reference = trim((string) ($data['reference'] ?? '')) ?: 'BULK-'.strtoupper(substr($batchToken, 0, 12));

        $account = ! empty($data['payment_account_id'])
            ? \App\Models\PaymentAccount::find($data['payment_account_id'])
            : null;
        $accountBaseBalance = $account?->liveBalance() ?? 0.0;
        $accountAdded = 0.0;

        $processingParty = null;
        $processingStep = 'loading selected parties';

        try {
            $result = DB::transaction(function () use ($customerIds, $data, $duration, $paymentDate, $reference, $entryBy, $account, $accountBaseBalance, &$accountAdded, &$processingParty, &$processingStep): array {
                $customers = Customer::query()
                    ->whereKey($customerIds)
                    ->lockForUpdate()
                    ->orderBy('id')
                    ->get();

                if ($customers->count() !== count($customerIds)) {
                    throw new InvalidArgumentException('One or more selected parties no longer exist.');
                }

                $customers->load(['activeSubscription.package', 'latestSubscription.package']);
                $total = 0.0;
                $invoiceIds = [];

                foreach ($customers as $customer) {
                    $processingParty = $customer;
                    $processingStep = 'validating assigned package';
                    $subscription = $customer->activeSubscription ?: $customer->latestSubscription;
                    $package = $subscription?->package;

                    if (! $subscription || ! $package) {
                        throw new InvalidArgumentException('No assigned package was found.');
                    }

                    $amount = $this->amountForPrice($subscription->effectivePrice(), $duration);
                    if ($amount <= 0) {
                        throw new InvalidArgumentException('The assigned package has a zero price.');
                    }

                    if ($account && $account->balance_limit !== null) {
                        $accountAdded += $amount;
                        if (round($accountBaseBalance + $accountAdded, 2) > (float) $account->balance_limit + 0.001) {
                            $processingStep = 'checking the account balance limit';
                            throw new InvalidArgumentException(sprintf(
                                'The "%s" account would pass its balance limit of BDT %s during this batch. Deposit its balance to the office first.',
                                $account->account_name,
                                number_format((float) $account->balance_limit, 2),
                            ));
                        }
                    }

                    $note = trim((string) ($data['note'] ?? ''));
                    $durationLabel = $this->durationOptions()[$duration];
                    $paymentNote = trim("Bulk {$durationLabel} package payment for {$package->name}. Reference: {$reference}. {$note}");

                    $processingStep = 'creating invoice';
                    $invoice = Invoice::create([
                        'entry_by' => $entryBy,
                        'customer_id' => $customer->id,
                        'invoice_no' => Invoice::generateInvoiceNo($customer->id, $paymentDate->format('Y-m')),
                        'billing_month' => $paymentDate->format('Y-m'),
                        'invoice_type' => 'service',
                        'subtotal' => $amount,
                        'gross_total' => $amount,
                        'discount' => 0,
                        'discount_type' => 'amount',
                        'discount_value' => 0,
                        'vat' => 0,
                        'vat_type' => 'amount',
                        'vat_value' => 0,
                        'total' => $amount,
                        'paid_amount' => $amount,
                        'due_amount' => 0,
                        'status' => 'paid',
                        'finalized_at' => now(),
                        'due_date' => $paymentDate->toDateString(),
                        'payment_note' => $paymentNote,
                        'private_note' => $paymentNote,
                    ]);

                    $processingStep = 'recording payment';
                    $payment = Payment::create([
                        'entry_by' => $entryBy,
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_method' => $data['payment_method'],
                        'payment_account_id' => $data['payment_account_id'] ?? null,
                        'payment_date' => $paymentDate->toDateString(),
                        'note' => $paymentNote,
                    ]);

                    $processingStep = 'allocating payment to invoice';
                    PaymentAllocation::create([
                        'entry_by' => $entryBy,
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'payment_id' => $payment->id,
                        'source_type' => 'payment',
                        'amount' => $amount,
                        'allocated_at' => $paymentDate->toDateString(),
                        'note' => $paymentNote,
                    ]);

                    $processingStep = 'extending service validity';
                    $periodStart = $customer->service_valid_until
                        && $customer->service_valid_until->copy()->startOfDay()->gte($paymentDate)
                            ? $customer->service_valid_until->copy()->startOfDay()->addDay()
                            : $paymentDate->copy();
                    $periodEnd = $duration === 'month_1'
                        ? $periodStart->copy()->addMonthNoOverflow()->subDay()
                        : $periodStart->copy()->addDays($this->durationDays($duration) - 1);
                    $validityNote = sprintf(
                        '[%s] Bulk invoice %s paid: %s, %s to %s, amount %s, reference %s.',
                        now()->format('d/m/Y H:i'),
                        $invoice->invoice_no,
                        $durationLabel,
                        $periodStart->format('d/m/Y'),
                        $periodEnd->format('d/m/Y'),
                        number_format($amount, 2, '.', ''),
                        $reference,
                    );

                    $customer->update([
                        'status' => 'active',
                        'service_valid_from' => $periodStart->toDateString(),
                        'service_valid_until' => $periodEnd->toDateString(),
                        'service_validity_note' => $validityNote,
                        'grace_until' => null,
                        'grace_days' => null,
                        'grace_used_at' => null,
                        'notes' => trim(implode("\n", array_filter([$customer->notes, $validityNote]))),
                    ]);
                    $subscription->update(['status' => 'active', 'end_date' => null]);
                    $invoiceIds[] = $invoice->id;
                    $total = round($total + $amount, 2);
                }

                return ['customers' => $customers, 'invoice_ids' => $invoiceIds, 'total' => $total];
            });
        } catch (Throwable $exception) {
            $partyLabel = $processingParty
                ? "Party #{$processingParty->id} ({$processingParty->name})"
                : 'Selected party batch';
            throw new InvalidArgumentException(
                "{$partyLabel} failed while {$processingStep}: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        $syncFailures = 0;
        foreach ($result['customers'] as $customer) {
            try {
                $this->mikrotikSyncService->sync($customer->refresh());
            } catch (Throwable $exception) {
                $syncFailures++;
                Log::warning('MikroTik sync failed after bulk customer payment.', [
                    'customer_id' => $customer->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'count' => $result['customers']->count(),
            'invoice_count' => count($result['invoice_ids']),
            'total' => $result['total'],
            'sync_failures' => $syncFailures,
        ];
    }

    private function durationDays(string $duration): int
    {
        return match ($duration) {
            'day_1' => 1,
            'day_7' => 7,
            'day_14' => 14,
            default => throw new InvalidArgumentException('Invalid day duration.'),
        };
    }
}
