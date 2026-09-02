<?php

namespace App\Support;

class Ipv4Range
{
    public static function contains(string $ranges, string $ipAddress): bool
    {
        $ip = self::toUnsignedInteger($ipAddress);
        if ($ip === null) {
            return false;
        }

        foreach (preg_split('/[\s,;]+/', trim($ranges), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $range) {
            if (self::rangeContains($range, $ip)) {
                return true;
            }
        }

        return false;
    }

    private static function rangeContains(string $range, int $ip): bool
    {
        $range = trim($range);

        if (str_contains($range, '/')) {
            [$networkAddress, $prefix] = array_pad(explode('/', $range, 2), 2, null);
            $network = self::toUnsignedInteger(trim((string) $networkAddress));
            if ($network === null || ! ctype_digit((string) $prefix)) {
                return false;
            }

            $prefix = (int) $prefix;
            if ($prefix < 0 || $prefix > 32) {
                return false;
            }

            $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);

            return ($ip & $mask) === ($network & $mask);
        }

        if (str_contains($range, '-')) {
            [$startAddress, $endAddress] = array_pad(explode('-', $range, 2), 2, null);
            $start = self::toUnsignedInteger(trim((string) $startAddress));
            $end = self::toUnsignedInteger(trim((string) $endAddress));
            if ($start === null || $end === null) {
                return false;
            }

            return $ip >= min($start, $end) && $ip <= max($start, $end);
        }

        $single = self::toUnsignedInteger($range);

        return $single !== null && $ip === $single;
    }

    private static function toUnsignedInteger(string $ipAddress): ?int
    {
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        $value = ip2long($ipAddress);

        return $value === false ? null : (int) sprintf('%u', $value);
    }
}
