<?php

namespace App\Support;

/**
 * MAC-address normalisation. RouterOS `$"caller-id"` (the PPPoE client MAC)
 * and the OLT-reported ONU serial / learned MACs use different casing and
 * separators, so comparisons run on a canonical form.
 */
class Mac
{
    /** Bare lowercase hex, e.g. "008dff022a17"; empty string when not a MAC. */
    public static function hex(?string $raw): string
    {
        $hex = strtolower(preg_replace('/[^0-9a-fA-F]/', '', (string) $raw));

        return strlen($hex) === 12 ? $hex : '';
    }

    /** Lowercase colon form, e.g. "00:8d:ff:02:2a:17"; null when not a MAC. */
    public static function colon(?string $raw): ?string
    {
        $hex = self::hex($raw);

        return $hex === '' ? null : implode(':', str_split($hex, 2));
    }
}
