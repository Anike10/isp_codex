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
    public const SKIP_DAYS_KEY = 'billing_disable_skip_days';

    public const DEFAULT_START = 12;
    public const DEFAULT_END = 17;

    /** @var array<int, string> ISO-8601 weekday number => label */
    public const DAY_OPTIONS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

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

    /** @return array<int, int> ISO-8601 weekday numbers (Monday=1, Sunday=7) */
    public static function skipDays(): array
    {
        $saved = AppSetting::value(self::SKIP_DAYS_KEY, '[]');
        $days = json_decode((string) $saved, true);

        if (! is_array($days)) {
            $days = preg_split('/\s*,\s*/', (string) $saved, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        $days = array_values(array_unique(array_filter(
            array_map(fn (mixed $day): int => (int) $day, $days),
            fn (int $day): bool => isset(self::DAY_OPTIONS[$day])
        )));
        sort($days);

        return $days;
    }

    /** @return array<int, string> */
    public static function dayOptions(): array
    {
        return self::DAY_OPTIONS;
    }

    public static function isSkippedDay(?CarbonInterface $now = null): bool
    {
        $now = $now ? Carbon::parse($now) : Carbon::now();

        return in_array((int) $now->format('N'), self::skipDays(), true);
    }

    /** Whether the auto-disable job is allowed to run at the given moment. */
    public static function isOpenNow(?CarbonInterface $now = null): bool
    {
        $now = $now ? Carbon::parse($now) : Carbon::now();

        if (self::isSkippedDay($now)) {
            return false;
        }

        ['start' => $start, 'end' => $end] = self::window();
        $hour = (int) $now->format('G');

        return $hour >= $start && $hour <= $end;
    }

    public static function label(): string
    {
        ['start' => $start, 'end' => $end] = self::window();

        return sprintf('%02d:00-%02d:00', $start, $end);
    }

    public static function skipDaysLabel(): string
    {
        return collect(self::skipDays())
            ->map(fn (int $day): string => self::DAY_OPTIONS[$day])
            ->implode(', ');
    }

    public static function scheduleLabel(): string
    {
        $skipDays = self::skipDaysLabel();

        return self::label().($skipDays !== '' ? ' except '.$skipDays : ' every day');
    }

    public static function closedReason(?CarbonInterface $now = null): ?string
    {
        $now = $now ? Carbon::parse($now) : Carbon::now();

        if (self::isSkippedDay($now)) {
            return self::DAY_OPTIONS[(int) $now->format('N')].' is configured as a no-auto-disable day';
        }

        ['start' => $start, 'end' => $end] = self::window();
        $hour = (int) $now->format('G');

        return $hour < $start || $hour > $end
            ? 'the current time is outside the configured '.self::label().' window'
            : null;
    }

    private static function clampHour(mixed $value, int $fallback): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $fallback;
        }

        return max(0, min(23, (int) $value));
    }
}
