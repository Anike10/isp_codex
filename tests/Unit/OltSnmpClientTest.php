<?php

namespace Tests\Unit;

use App\Models\OltOnu;
use App\Services\OltSnmpClient;
use Tests\TestCase;

class OltSnmpClientTest extends TestCase
{
    public function test_it_fills_single_onu_oid_templates(): void
    {
        $client = new OltSnmpClient();

        $oid = $client->fillOidTemplate(
            '.1.3.6.1.4.1.999.{pon_port}.{onu_id}.{pon_onu_dot}',
            new OltOnu(['pon_port' => 7, 'onu_id' => 14])
        );

        $this->assertSame('.1.3.6.1.4.1.999.7.14.7.14', $oid);
    }

    public function test_it_normalizes_common_snmp_status_values(): void
    {
        $client = new OltSnmpClient();

        $this->assertSame('online', $client->normalizeStatus('INTEGER: 1'));
        $this->assertSame('online', $client->normalizeStatus('STRING: "online"'));
        $this->assertSame('offline', $client->normalizeStatus('INTEGER: 2'));
        $this->assertSame('offline', $client->normalizeStatus('STRING: "LOS"'));
    }

    public function test_it_normalizes_power_with_divisor(): void
    {
        $client = new OltSnmpClient();

        $this->assertSame(-23.8, $client->normalizePower('INTEGER: -238', 10));
        $this->assertSame(-24.35, $client->normalizePower('STRING: "-24.35"', 1));
        $this->assertNull($client->normalizePower('No Such Object', 10));
    }
}
