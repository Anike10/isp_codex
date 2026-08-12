<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkash_sms_payments')) {
            return;
        }

        DB::table('bkash_sms_payments')
            ->whereNotNull('payment_date')
            ->whereNotNull('raw_sms')
            ->orderBy('id')
            ->chunkById(100, function ($smsPayments): void {
                foreach ($smsPayments as $smsPayment) {
                    $correctDate = $this->dateFromSms((string) $smsPayment->raw_sms);

                    if (! $correctDate) {
                        continue;
                    }

                    $wrongDate = Carbon::parse($smsPayment->payment_date)->toDateString();
                    $correctDateString = $correctDate->toDateString();

                    if ($correctDateString === $wrongDate) {
                        continue;
                    }

                    DB::table('bkash_sms_payments')->where('id', $smsPayment->id)->update([
                        'payment_date' => $correctDateString,
                    ]);

                    if ($smsPayment->payment_id && Schema::hasTable('payments')) {
                        DB::table('payments')->where('id', $smsPayment->payment_id)->update([
                            'payment_date' => $correctDateString,
                        ]);

                        if (Schema::hasTable('payment_allocations')) {
                            DB::table('payment_allocations')->where('payment_id', $smsPayment->payment_id)->update([
                                'allocated_at' => $correctDateString,
                            ]);
                        }

                        if (Schema::hasTable('customer_balance_transactions')) {
                            DB::table('customer_balance_transactions')->where('payment_id', $smsPayment->payment_id)->update([
                                'transaction_date' => $correctDateString,
                            ]);
                        }
                    }

                    $this->repairLatestValidity($smsPayment, $wrongDate, $correctDate);
                }
            });
    }

    public function down(): void
    {
        // The previous month/day guesses cannot be restored safely.
    }

    private function dateFromSms(string $rawSms): ?Carbon
    {
        if (! preg_match('/(?:at|on)\s+([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4})\s+([0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?\s*(?:AM|PM)?)/i', preg_replace('/\s+/', ' ', trim($rawSms)), $match)) {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', strtoupper(trim($match[1].' '.$match[2])));
        $dateParts = explode('/', $match[1]);
        $yearFormat = strlen((string) end($dateParts)) === 2 ? 'y' : 'Y';
        $hasMeridiem = preg_match('/\s(?:AM|PM)$/', $value) === 1;
        $hasSeconds = preg_match('/\d{1,2}:\d{2}:\d{2}/', $value) === 1;
        $timeFormat = $hasMeridiem
            ? ($hasSeconds ? 'h:i:s A' : 'h:i A')
            : ($hasSeconds ? 'H:i:s' : 'H:i');

        try {
            return Carbon::createFromFormat('!d/m/'.$yearFormat.' '.$timeFormat, $value, config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }

    private function repairLatestValidity(object $smsPayment, string $wrongDate, Carbon $correctDate): void
    {
        if (! $smsPayment->customer_id || ! $smsPayment->trx_id || ! Schema::hasTable('customers')) {
            return;
        }

        $customer = DB::table('customers')->where('id', $smsPayment->customer_id)->first([
            'id', 'service_valid_from', 'service_validity_note', 'notes',
        ]);

        $oldDetail = (string) ($customer?->service_validity_note ?? '');
        if (! $customer
            || ! $customer->service_valid_from
            || Carbon::parse($customer->service_valid_from)->toDateString() !== $wrongDate
            || ! str_contains($oldDetail, (string) $smsPayment->trx_id)) {
            return;
        }

        preg_match('/grace deducted\s+(\d+)\s+day\(s\)/i', $oldDetail, $graceMatch);
        $monthDays = max(1, $correctDate->copy()->diffInDays($correctDate->copy()->addMonthNoOverflow()));
        $graceDays = min(max(0, (int) ($graceMatch[1] ?? 0)), max(0, $monthDays - 1));
        $validDays = $monthDays - $graceDays;
        $validUntil = $correctDate->copy()->addDays($validDays - 1);
        $paymentNote = str_contains($oldDetail, ' Payment note: ')
            ? ' Payment note: '.str($oldDetail)->after(' Payment note: ')->toString()
            : '';
        $auditTime = $smsPayment->created_at
            ? Carbon::parse($smsPayment->created_at)->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');
        $newDetail = sprintf(
            '[%s] Paid validity: payment date %s; one-month period %d day(s); grace deducted %d day(s); validity %d day(s), %s to %s.%s',
            $auditTime,
            $correctDate->format('d/m/Y'),
            $monthDays,
            $graceDays,
            $validDays,
            $correctDate->format('d/m/Y'),
            $validUntil->format('d/m/Y'),
            $paymentNote
        );

        DB::table('customers')->where('id', $customer->id)->update([
            'service_valid_from' => $correctDate->toDateString(),
            'service_valid_until' => $validUntil->toDateString(),
            'service_validity_note' => $newDetail,
            'notes' => $customer->notes
                ? str_replace($oldDetail, $newDetail, (string) $customer->notes)
                : $customer->notes,
        ]);
    }
};
