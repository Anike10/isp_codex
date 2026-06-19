<?php

namespace Tests\Unit;

use App\Support\SerialNumberParser;
use PHPUnit\Framework\TestCase;

class SerialNumberParserTest extends TestCase
{
    public function test_it_expands_ascii_serial_ranges(): void
    {
        $serials = (new SerialNumberParser)->parse('1001-1004');

        $this->assertSame(['1001', '1002', '1003', '1004'], $serials);
    }

    public function test_it_expands_bengali_digit_serial_ranges(): void
    {
        $serials = (new SerialNumberParser)->parse("\u{09E7}\u{09E6}\u{09E6}\u{09E7}-\u{09E7}\u{09E6}\u{09E6}\u{09EA}");

        $this->assertSame([
            "\u{09E7}\u{09E6}\u{09E6}\u{09E7}",
            "\u{09E7}\u{09E6}\u{09E6}\u{09E8}",
            "\u{09E7}\u{09E6}\u{09E6}\u{09E9}",
            "\u{09E7}\u{09E6}\u{09E6}\u{09EA}",
        ], $serials);
    }

    public function test_it_expands_bengali_from_word_ranges(): void
    {
        $serials = (new SerialNumberParser)->parse("ONU010 \u{09A5}\u{09C7}\u{0995}\u{09C7} ONU011");

        $this->assertSame(['ONU010', 'ONU011'], $serials);
    }

    public function test_it_formats_serials_as_compact_comma_separated_ranges(): void
    {
        $serials = (new SerialNumberParser)->formatCompact("ONU001\nONU002\nONU003\nONU010\nONU012\nONU013");

        $this->assertSame('ONU001 to ONU003, ONU010, ONU012 to ONU013', $serials);
    }

    public function test_it_formats_bengali_digit_serials_as_compact_ranges(): void
    {
        $serials = (new SerialNumberParser)->formatCompact("\u{09E7}\u{09E6}\u{09E6}\u{09E7}\n\u{09E7}\u{09E6}\u{09E6}\u{09E8}\n\u{09E7}\u{09E6}\u{09E6}\u{09EA}");

        $this->assertSame("\u{09E7}\u{09E6}\u{09E6}\u{09E7} to \u{09E7}\u{09E6}\u{09E6}\u{09E8}, \u{09E7}\u{09E6}\u{09E6}\u{09EA}", $serials);
    }
}
