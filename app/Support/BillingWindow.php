<?php

namespace App\Support;

use App\Models\AppSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Daily time window during which the "disable overdue / expired parties" job is
 * allowed to run. Kept away from the night by default so parties are never cut
 * off while the office is closed.
 */
class BillingWindow
{
    public const START_KEY = 'billing_disable_start_hour';
    public const END_KEY = 'billing_disable_end_hour';

    public const DEFAULT_START = 12;
    public const DEFAULT_END = 17;

    /** @return array{start: int, end: int} */
    public static function window(): array
    {
        $start = self::clampHour(AppSetting::value(self::START_KEY), self::DEFAULT_START);
        $end = self::clampHour(AppSetting::value(self::END_KEY), self::DEFAULT_END);

        if ($end < $start) {
            $end = $start;
        }

        return ['start' => $start, 'end' => $end];
    }

    /** Whether the auto-disable job is allowed to run at the given moment. */
    public static function isOpenNow(?CarbonInterface $now = null): bool
    {
        $now = $now ? Carbon::parse($now) : Carbon::now();
        ['start' => $start, 'end' => $end] = self::window();
        $hour = (int) $now->format('G');

        return $hour >= $start && $hour <= $end;
    }

    public static function label(): string
    {
        ['start' => $start, 'end' => $end] = self::window();

        return sprintf('%02d:00-%02d:00', $start, $end);
    }

    private static function clampHour(mixed $value, int $fallback): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $fallback;
        }

        return max(0, min(23, (int) $value));
    }
}
