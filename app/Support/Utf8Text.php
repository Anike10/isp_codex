<?php

namespace App\Support;

class Utf8Text
{
    public static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace("\0", '', $value);

        if (! mb_check_encoding($value, 'UTF-8')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            $value = $converted === false ? mb_convert_encoding($value, 'UTF-8', 'UTF-8') : $converted;
        }

        return preg_replace('/[^\P{C}\t\r\n]/u', '', $value) ?? $value;
    }
}
