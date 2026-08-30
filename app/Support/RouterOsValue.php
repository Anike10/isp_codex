<?php

namespace App\Support;

/**
 * Turns raw RouterOS `/print` field values into something an operator can
 * read at a glance — durations in days, percentages, byte counters in
 * GiB/MiB, link rates in Mbps — while the original string stays available
 * for a tooltip so nothing is lost.
 */
class RouterOsValue
{
    /** Fields whose numeric value is a raw byte count. */
    private const BYTE_KEYS = [
        'free-memory', 'total-memory', 'free-hdd-space', 'total-hdd-space',
        'hdd-space', 'write-sect-total', 'total-memory-kib',
    ];

    /** Fields that read better as a monospaced technical token. */
    private const MONO_KEYS = [
        '.id', 'id', 'mac-address', 'active-mac-address', 'caller-id', 'address',
        'remote-address', 'local-address', 'gateway', 'dst-address', 'src-address',
        'network', 'ranges', 'client-id', 'server', 'active-address', 'to-addresses',
    ];

    /** Human display text for one field. */
    public static function humanize(string $key, string $value): string
    {
        $key = strtolower(trim($key));
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($key === 'uptime' || str_ends_with($key, '-uptime')) {
            return self::duration($value);
        }

        if (($key === 'cpu-load' || str_ends_with($key, '-cpu-load') || $key === 'cpu-usage') && is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%';
        }

        if (is_numeric($value) && (
            in_array($key, self::BYTE_KEYS, true)
            || str_ends_with($key, '-byte')
            || str_ends_with($key, '-memory')
            || str_ends_with($key, '-hdd-space')
        )) {
            return self::bytes((float) $value);
        }

        if (is_numeric($value) && (str_ends_with($key, '-bits-per-second') || str_ends_with($key, 'bps'))) {
            return self::bits((float) $value);
        }

        if (is_numeric($value) && (str_ends_with($key, '-packets-per-second') || str_ends_with($key, 'pps'))) {
            return number_format((float) $value).' pps';
        }

        return $value;
    }

    /** '', 'mono', 'bool-on' or 'bool-off' — a styling hint for the cell. */
    public static function kind(string $key, string $value): string
    {
        $value = trim($value);
        $key = strtolower(trim($key));

        if ($value === 'true' || $value === 'yes') {
            return 'bool-on';
        }
        if ($value === 'false' || $value === 'no') {
            return 'bool-off';
        }
        if (in_array($key, self::MONO_KEYS, true)
            || preg_match('/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/i', $value)
            || preg_match('/^\d{1,3}(\.\d{1,3}){3}(\/\d{1,2})?$/', $value)) {
            return 'mono';
        }

        return '';
    }

    /** RouterOS duration string ("3w1d12h50m46s") → "22d 12h". */
    public static function duration(string $value): string
    {
        if (! preg_match('/^(?:\d+w)?(?:\d+d)?(?:\d+h)?(?:\d+m)?(?:\d+s)?$/', $value) || $value === '') {
            return $value;
        }

        preg_match_all('/(\d+)([wdhms])/', $value, $parts, PREG_SET_ORDER);
        $unit = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];
        $seconds = 0;
        foreach ($parts as $part) {
            $seconds += (int) $part[1] * $unit[$part[2]];
        }
        if ($seconds <= 0) {
            return $value;
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return $hours > 0 ? "{$days}d {$hours}h" : "{$days}d";
        }
        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }

    private static function bytes(float $n): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        $i = 0;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) (int) $n : number_format($n, $n < 10 ? 2 : 1)).' '.$units[$i];
    }

    private static function bits(float $n): string
    {
        $units = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
        $i = 0;
        while ($n >= 1000 && $i < count($units) - 1) {
            $n /= 1000;
            $i++;
        }

        return ($i === 0 ? (string) (int) $n : number_format($n, $n < 10 ? 2 : 1)).' '.$units[$i];
    }
}
