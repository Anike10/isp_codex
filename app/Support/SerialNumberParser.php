<?php

namespace App\Support;

use InvalidArgumentException;

class SerialNumberParser
{
    public const MAX_SERIALS_PER_INPUT = 1000;

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

    private function parsePart(string $part): array
    {
        if (! preg_match('/^([A-Za-z_-]*)(\d+)\s*(?:-|to|থেকে)\s*([A-Za-z_-]*)(\d+)$/iu', $part, $match)) {
            return [$part];
        }

        $startPrefix = $match[1];
        $startNumber = (int) $match[2];
        $endPrefix = $match[3] !== '' ? $match[3] : $startPrefix;
        $endNumber = (int) $match[4];

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

        $width = max(strlen($match[2]), strlen($match[4]));
        $serials = [];

        for ($number = $startNumber; $number <= $endNumber; $number++) {
            $serials[] = $startPrefix.str_pad((string) $number, $width, '0', STR_PAD_LEFT);
        }

        return $serials;
    }
}
