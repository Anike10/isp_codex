<?php

namespace App\Support;

use InvalidArgumentException;

class SerialNumberParser
{
    public const MAX_SERIALS_PER_INPUT = 1000;

    private const BENGALI_DIGITS = [
        "\u{09E6}" => '0',
        "\u{09E7}" => '1',
        "\u{09E8}" => '2',
        "\u{09E9}" => '3',
        "\u{09EA}" => '4',
        "\u{09EB}" => '5',
        "\u{09EC}" => '6',
        "\u{09ED}" => '7',
        "\u{09EE}" => '8',
        "\u{09EF}" => '9',
    ];

    public function parse(string $value): array
    {
        $serials = [];
        $parts = preg_split('/[\r\n,]+/', $value) ?: [];

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            array_push($serials, ...$this->parsePart($part));
        }

        $serials = array_values(array_unique($serials));

        if (count($serials) > self::MAX_SERIALS_PER_INPUT) {
            throw new InvalidArgumentException('Serial input cannot contain more than '.self::MAX_SERIALS_PER_INPUT.' serial numbers.');
        }

        return $serials;
    }

    public function formatCompact(?string $value): string
    {
        $serials = $this->parse((string) $value);

        if ($serials === []) {
            return '';
        }

        $groups = [];
        $current = null;

        foreach ($serials as $serial) {
            $parts = $this->splitSerial($serial);

            if (
                $current
                && $parts
                && $current['parts']
                && $parts['prefix'] === $current['parts']['prefix']
                && $parts['number'] === $current['parts']['number'] + 1
            ) {
                $current['end'] = $serial;
                $current['parts'] = $parts;
                continue;
            }

            if ($current) {
                $groups[] = $current;
            }

            $current = [
                'start' => $serial,
                'end' => $serial,
                'parts' => $parts,
            ];
        }

        if ($current) {
            $groups[] = $current;
        }

        return implode(', ', array_map(
            fn (array $group): string => $group['start'] === $group['end']
                ? $group['start']
                : $group['start'].' to '.$group['end'],
            $groups,
        ));
    }

    private function parsePart(string $part): array
    {
        $digits = '0-9\x{09E6}-\x{09EF}';
        $from = '\x{09A5}\x{09C7}\x{0995}\x{09C7}';

        if (! preg_match('/^([\p{L}_-]*)(['.$digits.']+)\s*(?:-|to|'.$from.')\s*([\p{L}_-]*)(['.$digits.']+)$/iu', $part, $match)) {
            return [$part];
        }

        $startPrefix = $match[1];
        $startNumberText = $this->normalizeDigits($match[2]);
        $endNumberText = $this->normalizeDigits($match[4]);
        $startNumber = (int) $startNumberText;
        $endPrefix = $match[3] !== '' ? $match[3] : $startPrefix;
        $endNumber = (int) $endNumberText;

        if ($startPrefix !== $endPrefix) {
            throw new InvalidArgumentException('Serial range prefixes must match.');
        }

        if ($endNumber < $startNumber) {
            throw new InvalidArgumentException('Serial range end cannot be smaller than start.');
        }

        $count = $endNumber - $startNumber + 1;

        if ($count > self::MAX_SERIALS_PER_INPUT) {
            throw new InvalidArgumentException('A serial range cannot contain more than '.self::MAX_SERIALS_PER_INPUT.' serial numbers.');
        }

        $width = max(strlen($startNumberText), strlen($endNumberText));
        $serials = [];
        $useBengaliDigits = $this->containsBengaliDigits($match[2]);

        for ($number = $startNumber; $number <= $endNumber; $number++) {
            $serialNumber = str_pad((string) $number, $width, '0', STR_PAD_LEFT);
            $serials[] = $startPrefix.($useBengaliDigits ? $this->toBengaliDigits($serialNumber) : $serialNumber);
        }

        return $serials;
    }

    private function splitSerial(string $serial): ?array
    {
        $digits = '0-9\x{09E6}-\x{09EF}';

        if (! preg_match('/^(.*?)(['.$digits.']+)$/u', $serial, $match)) {
            return null;
        }

        return [
            'prefix' => $match[1],
            'number' => (int) $this->normalizeDigits($match[2]),
        ];
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, self::BENGALI_DIGITS);
    }

    private function containsBengaliDigits(string $value): bool
    {
        return preg_match('/[\x{09E6}-\x{09EF}]/u', $value) === 1;
    }

    private function toBengaliDigits(string $value): string
    {
        return strtr($value, array_flip(self::BENGALI_DIGITS));
    }
}
