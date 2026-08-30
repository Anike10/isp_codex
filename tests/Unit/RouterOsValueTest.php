<?php

namespace Tests\Unit;

use App\Support\RouterOsValue;
use PHPUnit\Framework\TestCase;

class RouterOsValueTest extends TestCase
{
    public function test_uptime_is_shown_in_days(): void
    {
        $this->assertSame('22d 12h', RouterOsValue::humanize('uptime', '3w1d12h50m46s'));
        $this->assertSame('1d', RouterOsValue::humanize('uptime', '1d'));
        $this->assertSame('5h 10m', RouterOsValue::humanize('uptime', '5h10m3s'));
        $this->assertSame('45m', RouterOsValue::humanize('uptime', '45m'));
    }

    public function test_cpu_load_gets_a_percent_sign(): void
    {
        $this->assertSame('3%', RouterOsValue::humanize('cpu-load', '3'));
        $this->assertSame('12.5%', RouterOsValue::humanize('cpu-load', '12.5'));
    }

    public function test_byte_counters_become_human_readable(): void
    {
        $this->assertSame('3.00 GiB', RouterOsValue::humanize('free-memory', (string) (3 * 1024 ** 3)));
        $this->assertSame('184.0 MiB', RouterOsValue::humanize('rx-byte', '192920022'));
        $this->assertSame('900 B', RouterOsValue::humanize('tx-byte', '900'));
    }

    public function test_link_rate_becomes_mbps(): void
    {
        $this->assertSame('12.3 Mbps', RouterOsValue::humanize('rx-bits-per-second', '12300000'));
        $this->assertSame('2.50 Mbps', RouterOsValue::humanize('tx-bits-per-second', '2500000'));
    }

    public function test_unknown_fields_pass_through_untouched(): void
    {
        $this->assertSame('CCR2004-1G-12S+2XS', RouterOsValue::humanize('board-name', 'CCR2004-1G-12S+2XS'));
        $this->assertSame('pppoe-501', RouterOsValue::humanize('name', 'pppoe-501'));
    }

    public function test_kind_flags_booleans_and_technical_tokens(): void
    {
        $this->assertSame('bool-on', RouterOsValue::kind('running', 'true'));
        $this->assertSame('bool-off', RouterOsValue::kind('disabled', 'false'));
        $this->assertSame('mono', RouterOsValue::kind('address', '10.20.0.5'));
        $this->assertSame('mono', RouterOsValue::kind('caller-id', '00:8D:FF:02:2A:17'));
        $this->assertSame('', RouterOsValue::kind('comment', 'office link'));
    }
}
