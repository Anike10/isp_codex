<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOnuPowerSample;
use App\Support\OnuMatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

final class OnuSignalTicketService
{
    private const VERY_HIGH_RX_DBM = -10.0;

    private const HIGH_RX_DBM = -15.0;

    private const LOW_RX_DBM = -25.0;

    private const VERY_LOW_RX_DBM = -30.0;

    /**
     * Build editable ticket form defaults without writing anything to the database.
     *
     * @return array{customer_id: int, assigned_to: null, subject: string, description: string, priority: string, status: string}|null
     */
    public function draft(Customer $customer, Carbon $from, Carbon $to, float $swingThreshold): ?array
    {
        $swingThreshold = max(0.1, $swingThreshold);

        $samples = CustomerOnuPowerSample::query()
            ->with('oltOnu')
            ->where('customer_id', $customer->id)
            ->whereBetween('sampled_at', [$from, $to])
            ->orderBy('sampled_at')
            ->get();

        if ($samples->isEmpty()) {
            return null;
        }

        $latest = $samples->last();
        $rxValues = $samples->pluck('rx_power_dbm')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value)
            ->values();
        $latestRx = $latest->rx_power_dbm !== null ? (float) $latest->rx_power_dbm : null;
        $rxSwing = $rxValues->count() > 1 ? round((float) ($rxValues->max() - $rxValues->min()), 2) : 0.0;
        $isSwinging = $rxValues->count() > 1 && $rxSwing >= $swingThreshold;
        $statuses = $samples->pluck('status')->map(fn ($status) => trim((string) $status))->filter()->unique();
        $latestStatus = trim((string) $latest->status);
        $statusChanged = $statuses->count() > 1;
        $statusAbnormal = $latestStatus !== '' && ! in_array(mb_strtolower($latestStatus), ['online', 'up', 'active'], true);

        $powerAssessment = $this->powerAssessment($latestRx);
        $problems = [$powerAssessment['label']];
        if ($isSwinging) {
            $problems[] = 'লেজার পাওয়ার ওঠানামা করছে';
        }
        if ($statusChanged) {
            $problems[] = 'ONU স্ট্যাটাস পরিবর্তন হচ্ছে';
        } elseif ($statusAbnormal) {
            $problems[] = 'ONU বর্তমানে '.$latestStatus;
        }

        $onu = $latest->oltOnu ?: $this->matchedOnu($customer);
        $recommendations = $this->recommendations($powerAssessment['level'], $isSwinging, $statusChanged || $statusAbnormal);
        $priority = $this->priority($powerAssessment['level'], $isSwinging, $statusChanged, $statusAbnormal);
        $problemText = implode('; ', array_unique($problems));

        $description = collect([
            'স্বয়ংক্রিয় ONU লেজার পাওয়ার রিপোর্ট',
            '',
            'পার্টি: '.$customer->name,
            'সংযোগ আইডি: '.($customer->connection_id ?: 'পাওয়া যায়নি'),
            'মোবাইল: '.($customer->phone ?: 'পাওয়া যায়নি'),
            '',
            'সমস্যা: '.$problemText.'।',
            'পর্যবেক্ষণের সময়: '.$from->format('d/m/Y H:i').' থেকে '.$to->format('d/m/Y H:i'),
            'মোট নমুনা: '.$samples->count(),
            'সর্বশেষ নমুনা: '.$latest->sampled_at->format('d/m/Y H:i'),
            'সর্বশেষ Rx: '.$this->powerValue($latestRx),
            'সর্বশেষ Tx: '.$this->powerValue($latest->tx_power_dbm !== null ? (float) $latest->tx_power_dbm : null),
            'Rx সর্বনিম্ন/সর্বোচ্চ: '.($rxValues->isNotEmpty()
                ? $this->powerValue((float) $rxValues->min()).' / '.$this->powerValue((float) $rxValues->max())
                : 'পাওয়া যায়নি'),
            'Rx ওঠানামা: '.($rxValues->count() > 1 ? number_format($rxSwing, 2).' dB' : 'পর্যাপ্ত নমুনা নেই')
                .' (সীমা: '.number_format($swingThreshold, 2).' dB)',
            'ONU সিরিয়াল: '.($onu?->mac_address ?: 'পাওয়া যায়নি'),
            'OLT: '.($onu?->olt_name ?: 'পাওয়া যায়নি'),
            'PON/ONU: '.$this->ponOnuValue($onu?->pon_port, $onu?->onu_id),
            'ONU অবস্থা: '.($latestStatus ?: ($onu?->status ?: 'পাওয়া যায়নি')),
            '',
            'করণীয়: '.implode(' ', $recommendations),
        ])->implode(PHP_EOL);

        return [
            'customer_id' => $customer->id,
            'assigned_to' => null,
            'subject' => 'ONU সিগন্যাল: '.implode(' ও ', array_unique($problems)),
            'description' => $description,
            'priority' => $priority,
            'status' => 'open',
        ];
    }

    /** @return array{label: string, level: string} */
    private function powerAssessment(?float $rx): array
    {
        if ($rx === null) {
            return ['label' => 'Rx লেজার পাওয়ার পাওয়া যায়নি', 'level' => 'unknown'];
        }

        if ($rx > self::VERY_HIGH_RX_DBM) {
            return ['label' => 'লেজার পাওয়ার অনেক বেশি', 'level' => 'very_high'];
        }

        if ($rx > self::HIGH_RX_DBM) {
            return ['label' => 'লেজার পাওয়ার বেশি', 'level' => 'high'];
        }

        if ($rx < self::VERY_LOW_RX_DBM) {
            return ['label' => 'লেজার পাওয়ার অনেক কম', 'level' => 'very_low'];
        }

        if ($rx < self::LOW_RX_DBM) {
            return ['label' => 'লেজার পাওয়ার কম', 'level' => 'low'];
        }

        return ['label' => 'লেজার পাওয়ার স্বাভাবিক সীমায় আছে', 'level' => 'normal'];
    }

    /** @return array<int, string> */
    private function recommendations(string $level, bool $isSwinging, bool $statusProblem): array
    {
        $recommendations = [];

        if (in_array($level, ['very_high', 'high'], true)) {
            $recommendations[] = 'অপটিক্যাল পাওয়ার কমানোর প্রয়োজন আছে কি না এবং প্যাচ কর্ড, স্প্লিটার ও কনেক্টর পরীক্ষা করুন।';
        } elseif (in_array($level, ['very_low', 'low'], true)) {
            $recommendations[] = 'ফাইবার লস, বেন্ড, স্প্লাইস, প্যাচ কর্ড ও কনেক্টর পরীক্ষা করুন।';
        } elseif ($level === 'unknown') {
            $recommendations[] = 'ONU থেকে Rx পাওয়ার পাওয়া যাচ্ছে কি না পরীক্ষা করুন।';
        } else {
            $recommendations[] = 'বর্তমান পাওয়ার স্বাভাবিক; গ্রাহকের অভিযোগ ও অন্যান্য সংযোগ তথ্য যাচাই করুন।';
        }

        if ($isSwinging) {
            $recommendations[] = 'ফাইবারে টান বা অতিরিক্ত বেন্ড, ঢিলা কনেকশন, খারাপ স্প্লাইস বা প্যাচ কর্ড আছে কি না পরীক্ষা করুন।';
        }

        if ($statusProblem) {
            $recommendations[] = 'ONU পাওয়ার ও আপলিংক সংযোগ পরীক্ষা করুন।';
        }

        return $recommendations;
    }

    private function priority(string $level, bool $isSwinging, bool $statusChanged, bool $statusAbnormal): string
    {
        if (in_array($level, ['very_high', 'very_low'], true) || $statusAbnormal) {
            return 'urgent';
        }

        if (in_array($level, ['high', 'low'], true) || $isSwinging || $statusChanged) {
            return 'high';
        }

        return 'normal';
    }

    private function matchedOnu(Customer $customer)
    {
        if (! Schema::hasTable('olt_onus')) {
            return null;
        }

        $key = mb_strtolower(trim((string) $customer->last_connected_mac));

        return OnuMatcher::byMac([$customer->last_connected_mac])[$key] ?? null;
    }

    private function powerValue(?float $value): string
    {
        return $value === null ? 'পাওয়া যায়নি' : number_format($value, 2).' dBm';
    }

    private function ponOnuValue(mixed $ponPort, mixed $onuId): string
    {
        if ($ponPort === null && $onuId === null) {
            return 'পাওয়া যায়নি';
        }

        return ($ponPort ?? '—').'/'.($onuId ?? '—');
    }
}
