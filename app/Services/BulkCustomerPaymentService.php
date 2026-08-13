<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        $result = DB::transaction(function () use ($customerIds, $data, $duration, $paymentDate, $reference, $entryBy): array {
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

            foreach ($customers as $customer) {
                $subscription = $customer->activeSubscription ?: $customer->latestSubscription;
                $package = $subscription?->package;

                if (! $subscription || ! $package) {
                    throw new InvalidArgumentException("Party #{$customer->id} ({$customer->name}) has no assigned package.");
                }

                $amount = $this->amountForPrice((float) $package->monthly_price, $duration);
                if ($amount <= 0) {
                    throw new InvalidArgumentException("Party #{$customer->id} ({$customer->name}) has a zero-price package.");
                }

                $oldBalance = round((float) $customer->account_balance, 2);
                $temporaryBalance = round($oldBalance + $amount, 2);
                $note = trim((string) ($data['note'] ?? ''));
                $durationLabel = $this->durationOptions()[$duration];
                $transactionNote = trim("Bulk {$durationLabel} package payment for {$package->name}. {$note}");

                CustomerBalanceTransaction::create([
                    'entry_by' => $entryBy,
                    'customer_id' => $customer->id,
                    'payment_account_id' => $data['payment_account_id'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'direction' => 'credit',
                    'amount' => $amount,
                    'balance_after' => $temporaryBalance,
                    'transaction_date' => $paymentDate->toDateString(),
                    'reference' => $reference,
                    'operation_key' => (string) Str::uuid(),
                    'note' => $transactionNote,
                ]);

                CustomerBalanceTransaction::create([
                    'entry_by' => $entryBy,
                    'customer_id' => $customer->id,
                    'payment_account_id' => null,
                    'payment_method' => 'advance',
                    'direction' => 'debit',
                    'amount' => $amount,
                    'balance_after' => $oldBalance,
                    'transaction_date' => $paymentDate->toDateString(),
                    'reference' => $reference,
                    'operation_key' => (string) Str::uuid(),
                    'note' => 'Bulk payment applied to package validity.',
                ]);

                $periodStart = $customer->service_valid_until
                    && $customer->service_valid_until->copy()->startOfDay()->gte($paymentDate)
                        ? $customer->service_valid_until->copy()->startOfDay()->addDay()
                        : $paymentDate->copy();
                $periodEnd = $duration === 'month_1'
                    ? $periodStart->copy()->addMonthNoOverflow()->subDay()
                    : $periodStart->copy()->addDays($this->durationDays($duration) - 1);
                $validityNote = sprintf(
                    '[%s] Bulk payment: %s, %s to %s, amount %s, reference %s.',
                    now()->format('d/m/Y H:i'),
                    $durationLabel,
                    $periodStart->format('d/m/Y'),
                    $periodEnd->format('d/m/Y'),
                    number_format($amount, 2, '.', ''),
                    $reference,
                );

                $customer->update([
                    'status' => 'active',
                    'account_balance' => $oldBalance,
                    'service_valid_from' => $periodStart->toDateString(),
                    'service_valid_until' => $periodEnd->toDateString(),
                    'service_validity_note' => $validityNote,
                    'grace_until' => null,
                    'grace_days' => null,
                    'grace_used_at' => null,
                    'notes' => trim(implode("\n", array_filter([$customer->notes, $validityNote]))),
                ]);
                $subscription->update(['status' => 'active', 'end_date' => null]);
                $total = round($total + $amount, 2);
            }

            return ['customers' => $customers, 'total' => $total];
        });

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
