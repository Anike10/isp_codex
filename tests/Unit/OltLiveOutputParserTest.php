<?php

namespace Tests\Unit;

use App\Services\OltLiveOutputParser;
use PHPUnit\Framework\TestCase;

class OltLiveOutputParserTest extends TestCase
{
    public function test_it_parses_common_live_onu_power_output(): void
    {
        $output = <<<'OUTPUT'
PON/ONU     MAC Address          State     RxPower(dBm) Distance
1/1         00:8d:ff:02:2a:17    online    -25.50       1234
1/2         00:8d:ff:01:f7:f5    offline   -10.60       321
interface epon 2 onu 3
ONU Rx optical power(dBm): -18.79
description: KPI Office
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(3, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(1, $records[0]['onu_id']);
        $this->assertSame('00:8d:ff:02:2a:17', $records[0]['mac_address']);
        $this->assertSame('online', $records[0]['status']);
        $this->assertSame(-25.5, $records[0]['rx_power_dbm']);
        $this->assertSame(1234, $records[0]['distance_m']);
        $this->assertSame(2, $records[2]['pon_port']);
        $this->assertSame(3, $records[2]['onu_id']);
        $this->assertSame(-18.79, $records[2]['rx_power_dbm']);
        $this->assertSame('KPI Office', $records[2]['name']);
    }

    public function test_it_parses_hsgq_onu_info_and_optical_info_output(): void
    {
        $output = <<<'OUTPUT'
PON/ONU  Mac-Address        Status Auth Cfg Reg-time            ONU-Name     ONU-Desc
1/1      00:8d:ff:02:2a:17 Online TRUE TRUE 2026/05/18 09:43:42 Access krishi bank 13-10-25 -25
----------------------------------------------------------------------------------------------------
PON/ONU ONU-Name     Mac-address       Temperature  Voltage      Bias         Tx power     Rx power
----------------------------------------------------------------------------------------------------
    1/1 Access krishi bank 00:8d:ff:02:2a:17  43 C      3.22 V        18 mA       1.9296 dBm   -28.2391 dBm
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(1, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(1, $records[0]['onu_id']);
        $this->assertSame('00:8d:ff:02:2a:17', $records[0]['mac_address']);
        $this->assertSame('online', $records[0]['status']);
        $this->assertSame('Access krishi bank', $records[0]['name']);
        $this->assertSame('2026-05-18 09:43:42', $records[0]['last_registered_at']->format('Y-m-d H:i:s'));
        $this->assertSame(-28.2391, $records[0]['rx_power_dbm']);
    }

    public function test_it_ignores_invalid_utf8_terminal_bytes(): void
    {
        $output = "1/4     80:d4:a5:4d:6c:cf Online TRUE TRUE 2026/05/17 16:36:27 SonoHome \xE0\n"
            ."1/4 SonoHome 80:d4:a5:4d:6c:cf 47 C 3.27 V 10 mA 2.1838 dBm -24.8149 dBm";

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(1, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(4, $records[0]['onu_id']);
        $this->assertSame('80:d4:a5:4d:6c:cf', $records[0]['mac_address']);
        $this->assertSame('online', $records[0]['status']);
        $this->assertSame(-24.8149, $records[0]['rx_power_dbm']);
    }

    public function test_it_parses_warning_timestamps_as_deregister_history(): void
    {
        $output = <<<'OUTPUT'
[2026/05/18 15:18:23]  Warning: ONU 4/53 98:c7:a4:38:7c:93 Onu deregister, Reason:Laser out
all
----------------------------------------------------------------------------------------------------
PON/ONU     Mac-Address    Status   Auth  Cfg     Reg-time            ONU-Name     ONU-Desc
----------------------------------------------------------------------------------------------------
1/4     80:d4:a5:4d:6c:cf Online    TRUE  TRUE  2026/05/17 16:36:27      SonoHome     2/9/24
show optical-info
PON/ONU ONU-Name     Mac-address       Temperature  Voltage      Bias         Tx power     Rx power
1/4 SonoHome     80:d4:a5:4d:6c:cf  47 C      3.27 V        10 mA       2.1838 dBm   -24.8149 dBm
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(2, $records);
        $this->assertSame(4, $records[0]['pon_port']);
        $this->assertSame(53, $records[0]['onu_id']);
        $this->assertSame('98:c7:a4:38:7c:93', $records[0]['mac_address']);
        $this->assertSame('2026-05-18 15:18:23', $records[0]['last_deregistered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('Laser out', $records[0]['last_deregister_reason']);
        $this->assertSame(1, $records[1]['pon_port']);
        $this->assertSame(4, $records[1]['onu_id']);
        $this->assertSame('80:d4:a5:4d:6c:cf', $records[1]['mac_address']);
        $this->assertSame('2026-05-17 16:36:27', $records[1]['last_registered_at']->format('Y-m-d H:i:s'));
        $this->assertSame(-24.8149, $records[1]['rx_power_dbm']);
    }

    public function test_it_keeps_latest_alarm_history_for_each_onu(): void
    {
        $output = <<<'OUTPUT'
[2026/05/18 09:43:48]  Info: ONU 1/1 00:8d:ff:02:2a:17 ONU link up, Reason:
[2026/05/18 09:43:47]  Info: ONU 1/1 00:8d:ff:02:2a:17 ONU authorization success, Reason:
[2026/05/17 19:18:53]  Warning: ONU 1/1 00:8d:ff:02:2a:17 Onu deregister, Reason:Laser out
[2026/05/17 19:18:52]  Warning: ONU 1/1 00:8d:ff:02:2a:17 ONU dying gasp, Reason:
[2026/05/14 19:17:07]  Warning: ONU 1/1 00:8d:ff:02:2a:17 Onu deregister, Reason:Laser out
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(1, $records);
        $this->assertSame('2026-05-18 09:43:48', $records[0]['last_registered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-17 19:18:53', $records[0]['last_deregistered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('Laser out', $records[0]['last_deregister_reason']);
    }

    public function test_it_parses_onu_port_vlan_output(): void
    {
        $output = <<<'OUTPUT'
show port-vlan
 ONU : 1:7
----------------------------------------------------------------------------------------------------
 Port ID         Mode            Default Vlan
----------------------------------------------------------------------------------------------------
 port1           tag             id:21           pri:0
 port2           tag             id:101          pri:5
 port3           transparent
----------------------------------------------------------------------------------------------------
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(1, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(7, $records[0]['onu_id']);
        $this->assertSame([
            ['port' => 1, 'mode' => 'tag', 'vlan' => 21, 'priority' => 0],
            ['port' => 2, 'mode' => 'tag', 'vlan' => 101, 'priority' => 5],
            ['port' => 3, 'mode' => 'transparent', 'vlan' => null, 'priority' => null],
        ], $records[0]['port_vlans']);
    }
}
