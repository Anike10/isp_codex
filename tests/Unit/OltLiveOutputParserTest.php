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

    public function test_it_parses_hsgq_gpon_ont_alarm_history(): void
    {
        $output = <<<'OUTPUT'
[2026/05/18 17:10:11]  Info: ONT 1/12 XPONa4388257 ONT authorization success, Reason:
[2026/05/18 17:08:05]  Warning: ONT 1/12 XPONa4388257 ONT deregister, Reason:LOS
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(1, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(12, $records[0]['onu_id']);
        $this->assertSame('XPONa4388257', $records[0]['mac_address']);
        $this->assertSame('2026-05-18 17:10:11', $records[0]['last_registered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-18 17:08:05', $records[0]['last_deregistered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('LOS', $records[0]['last_deregister_reason']);
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

    public function test_it_parses_hsgq_epon_learned_mac_output(): void
    {
        $output = <<<'OUTPUT'
show mac-address epon all
Total PON mac address learning: 404
----------------------------------------------------------------------------------------------------
 PORT   ONU ID    MAC                     VLAN    MAC-Type  ONU-Name
----------------------------------------------------------------------------------------------------
 PON01  21        d4:01:c3:d7:dd:ed       999     dynamic   IBBL Kushtia Drick
 PON01  21        aa:bb:cc:dd:ee:ff       21      dynamic   IBBL Kushtia Drick
 PON01  21        aa:bb:cc:dd:ee:ff       115     dynamic   IBBL Kushtia Drick
 PON02  3         98:25:4a:aa:13:da       1       dynamic   Kpi_Office_huawi
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(2, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(21, $records[0]['onu_id']);
        $this->assertSame([
            ['mac' => 'd4:01:c3:d7:dd:ed', 'vlan' => 999, 'type' => 'dynamic', 'onu_name' => 'IBBL Kushtia Drick'],
            ['mac' => 'aa:bb:cc:dd:ee:ff', 'vlan' => 21, 'type' => 'dynamic', 'onu_name' => 'IBBL Kushtia Drick'],
            ['mac' => 'aa:bb:cc:dd:ee:ff', 'vlan' => 115, 'type' => 'dynamic', 'onu_name' => 'IBBL Kushtia Drick'],
        ], $records[0]['learned_macs']);
        $this->assertSame(2, $records[1]['pon_port']);
        $this->assertSame(3, $records[1]['onu_id']);
    }

    public function test_it_parses_hsgq_gpon_ont_info_and_optical_output(): void
    {
        $output = <<<'OUTPUT'
PON/ONU  Serial            State     Run        Config    Match     Last Link         ONT
         Number                      State      State     State     down reason       Name
1/0      D0111d106890      Active    Online     normal    Initial                     tisha_Surovi 13.3
1/12     XPONa4388257      Inactive  Initial    initial   Initial   LOS               Sabbir_Vagina
PON/ONU    ONT-SN    Temp Voltage    Bias   Tx power     Rx power     OLT Rx       ONT-Name
    1/0 D0111d106890 41 C 3.34 V   13.05 mA 1.8720 dBm   -13.4500 dBm -inf dBm     tisha_Surovi 13.3
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(2, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(0, $records[0]['onu_id']);
        $this->assertSame('D0111d106890', $records[0]['mac_address']);
        $this->assertSame('online', $records[0]['status']);
        $this->assertSame('tisha_Surovi 13.3', $records[0]['name']);
        $this->assertSame(-13.45, $records[0]['rx_power_dbm']);
        $this->assertSame(12, $records[1]['onu_id']);
        $this->assertSame('XPONa4388257', $records[1]['mac_address']);
        $this->assertSame('inactive', $records[1]['status']);
        $this->assertSame('LOS', $records[1]['description']);
    }

    public function test_it_parses_hsgq_gpon_ont_vlan_context_output(): void
    {
        $output = <<<'OUTPUT'
show port-vlan
 ONT : 1:12
----------------------------------------------------------------------------------------------------
 Port ID         Mode            Default Vlan
----------------------------------------------------------------------------------------------------
 port1           tag             id:1108          pri:0
 port2           transparent
----------------------------------------------------------------------------------------------------
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(1, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(12, $records[0]['onu_id']);
        $this->assertSame([
            ['port' => 1, 'mode' => 'tag', 'vlan' => 1108, 'priority' => 0],
            ['port' => 2, 'mode' => 'transparent', 'vlan' => null, 'priority' => null],
        ], $records[0]['port_vlans']);
    }

    public function test_it_parses_hsgq_gpon_service_port_and_learned_mac_tables(): void
    {
        $output = <<<'OUTPUT'
show service-port all
 INDEX VLAN PORT  ONT GEM  FLOW FLOW ETHER TAG           INNER STATE ADMIN   INSTALL MAC    TRAFFIC
 10    41   PON01 3   1    --   --   --    --            --    Up    Enable  Auto    --     0
show mac-address all
 SVP   MAC                 VLAN  Port    Ont  Gem   MAC-Type    ONT-Name
 10    80:af:ca:72:d3:d1   41    PON01   3    1     dynamic     Munna_Mamun
 39    80:af:ca:ba:ad:f3   21    PON02   3    1     dynamic     KPS_Prijom
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(2, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(3, $records[0]['onu_id']);
        $this->assertSame([
            ['port' => 1, 'mode' => 'service-port', 'vlan' => 41, 'priority' => null, 'service_port' => 10],
        ], $records[0]['port_vlans']);
        $this->assertSame([
            ['mac' => '80:af:ca:72:d3:d1', 'vlan' => 41, 'type' => 'dynamic', 'onu_name' => 'Munna_Mamun', 'service_port' => 10, 'gemport' => 1],
        ], $records[0]['learned_macs']);
        $this->assertSame(2, $records[1]['pon_port']);
    }

    public function test_it_parses_hsgq_gpon_ont_detail_times(): void
    {
        $output = <<<'OUTPUT'
show ont-info 3
 PON ID                        : 1
 ONU ID                        : 3
 ONU Name                      : Munna_Mamun
 SerialNumber                  : DF1Ba6f9799d
 Distance                      : 244
 Last up Time                  : 2026/05/18 21:49:30
 Last down Time                : 2026/05/18 21:39:14
 Last down cause               : LOS
OUTPUT;

        $records = (new OltLiveOutputParser())->parse($output);

        $this->assertCount(1, $records);
        $this->assertSame(1, $records[0]['pon_port']);
        $this->assertSame(3, $records[0]['onu_id']);
        $this->assertSame('Munna_Mamun', $records[0]['name']);
        $this->assertSame('DF1Ba6f9799d', $records[0]['mac_address']);
        $this->assertSame(244, $records[0]['distance_m']);
        $this->assertSame('2026-05-18 21:49:30', $records[0]['last_registered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-18 21:39:14', $records[0]['last_deregistered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('LOS', $records[0]['last_deregister_reason']);
    }
}
