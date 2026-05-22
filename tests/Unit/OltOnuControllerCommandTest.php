<?php

namespace Tests\Unit;

use App\Http\Controllers\OltOnuController;
use App\Models\OltDevice;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class OltOnuControllerCommandTest extends TestCase
{
    public function test_hsgq_gpon_authorize_commands_do_not_enter_ont_context(): void
    {
        $commands = $this->callPrivateCommandBuilder('authorizeOnuCommands', [
            new OltDevice(['protocol_profile' => 'hsgq_gpon']),
            null,
            $this->onuData(),
        ]);

        $this->assertSame([
            'interface gpon 1',
            'ont add 5 sn-auth HWTC12345678 desc "Customer 5" omci ont-lineprofile-id 0 ont-srvprofile-id 0 || ont add 5 sn-auth HWTC12345678 desc "Customer 5" ont-lineprofile-id 0 ont-srvprofile-id 0 || ont add 5 sn-auth HWTC12345678 omci ont-lineprofile-id 0 ont-srvprofile-id 0',
            '?ont modify 1 5 desc "Customer 5"',
            'ont port native-vlan 5 eth 1 vlan 100 0',
            'exit',
            'save',
        ], $commands);
    }

    public function test_existing_hsgq_gpon_authorize_commands_do_not_enter_ont_context(): void
    {
        $commands = $this->callPrivateCommandBuilder('authorizeExistingGponOnuCommands', [
            new OltDevice(['protocol_profile' => 'hsgq_gpon']),
            null,
            $this->onuData(),
        ]);

        $this->assertSame([
            'interface gpon 1',
            '?ont modify 1 5 desc "Customer 5"',
            'ont port native-vlan 5 eth 1 vlan 100 0',
            'exit',
            'save',
        ], $commands);
    }

    public function test_hsgq_epon_deny_authorize_removes_blacklist_before_bind(): void
    {
        $commands = $this->callPrivateCommandBuilder('authorizeOnuCommands', [
            new OltDevice(['protocol_profile' => 'hsgq_epon']),
            null,
            array_merge($this->onuData(), [
                'serial' => '70:a8:e3:f3:75:47',
                'source_type' => 'deny',
            ]),
        ]);

        $this->assertSame([
            'interface epon 1',
            '?blacklist delete mac 70:a8:e3:f3:75:47',
            'bind-onu 5 mac 70:a8:e3:f3:75:47 onu-type 1ge name "Customer 5"',
            'exit',
            'interface onu 1/5',
            'port-vlan 1 mode tag 100 pri 0',
            'exit',
            'save',
        ], $commands);
    }

    public function test_hsgq_epon_auto_authorize_omits_zero_onu_id(): void
    {
        $commands = $this->callPrivateCommandBuilder('authorizeAutoEponOnuCommands', [
            array_merge($this->onuData(), [
                'onu_id' => 0,
                'serial' => '70:a8:e3:f3:75:47',
                'source_type' => 'deny',
            ]),
        ]);

        $this->assertSame([
            'interface epon 1',
            '?blacklist delete mac 70:a8:e3:f3:75:47',
            'bind-onu mac 70:a8:e3:f3:75:47 onu-type 1ge name "Customer 5"',
            'show onu-info all',
            'exit',
        ], $commands);
    }

    public function test_hsgq_epon_write_access_uses_telnet_even_with_normalized_profile_key(): void
    {
        $method = $this->callPrivateCommandBuilder('writeAccessMethod', [
            new OltDevice([
                'access_method' => 'ssh',
                'protocol_profile' => ' HSGQ_EPON ',
            ]),
        ]);

        $this->assertSame('telnet', $method);
    }

    public function test_hsgq_epon_finds_auto_assigned_onu_id_from_output(): void
    {
        $onuId = $this->callPrivateCommandBuilder('findEponOnuIdInOutput', [
            "1/6  70:a8:e3:f3:75:47 Online TRUE TRUE 2026/05/21 15:00:00 alom",
            new OltDevice([
                'id' => 1,
                'name' => 'US_EPON',
                'protocol_profile' => 'hsgq_epon',
            ]),
            '70:a8:e3:f3:75:47',
        ]);

        $this->assertSame(6, $onuId);
    }

    public function test_utility_parser_ignores_olt_unknown_command_output(): void
    {
        $rows = $this->callPrivateCommandBuilder('parseUtilityRows', [
            'vty% [VTY] vty[node:7],Unknown command: show onu-autofindall',
            new OltDevice([
                'id' => 1,
                'name' => 'US_EPON',
                'protocol_profile' => 'hsgq_epon',
            ]),
            'discovery',
        ]);

        $this->assertSame([], $rows);
    }

    public function test_deny_parser_accepts_black_onu_rows_without_onu_id(): void
    {
        $rows = $this->callPrivateCommandBuilder('parseUtilityRows', [
            "PON  MAC\n1  80:af:ca:72:d3:d1  deny",
            new OltDevice([
                'id' => 1,
                'name' => 'US_EPON',
                'protocol_profile' => 'hsgq_epon',
            ]),
            'deny',
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]['pon_port']);
        $this->assertNull($rows[0]['source_onu_id']);
        $this->assertNull($rows[0]['onu_id']);
        $this->assertSame('80:af:ca:72:d3:d1', $rows[0]['serial']);
        $this->assertSame('deny', $rows[0]['status']);
    }

    public function test_hsgq_epon_deny_parser_uses_cfg_false_from_onu_info(): void
    {
        $rows = $this->callPrivateCommandBuilder('parseUtilityRows', [
            implode("\n", [
                'US_EPON(config)#interface epon 7',
                'interface epon 7',
                'ONU   MAC               Status   Auth  Cfg    RegisterTime          Name',
                '7/14  cc:52:89:05:85:f4 Initial  TRUE  FALSE  -                     mahin_top',
                '7/15  10:27:f5:11:22:33 Online   TRUE  TRUE   2026/05/21 14:59:06  active_user',
                '7/44  84:74:2a:1d:64:6f Online   TRUE  FALSE  2026/05/21 14:59:06  Kushtia_Trav',
            ]),
            new OltDevice([
                'id' => 1,
                'name' => 'US_EPON',
                'protocol_profile' => 'hsgq_epon',
            ]),
            'deny',
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame(44, $rows[0]['source_onu_id']);
        $this->assertSame('84:74:2a:1d:64:6f', $rows[0]['serial']);
    }

    public function test_hsgq_epon_deny_parser_accepts_blacklist_rows(): void
    {
        $rows = $this->callPrivateCommandBuilder('parseUtilityRows', [
            implode("\n", [
                'PON/ONU     Mac-Address        Blacklist_Reject_Count  Reason                  ONU-Name',
                '7/1         70:a8:e3:f3:75:47  64                                              B_ONU07/01',
            ]),
            new OltDevice([
                'id' => 1,
                'name' => 'US_EPON',
                'protocol_profile' => 'hsgq_epon',
            ]),
            'deny',
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame(7, $rows[0]['pon_port']);
        $this->assertSame(1, $rows[0]['source_onu_id']);
        $this->assertSame('70:a8:e3:f3:75:47', $rows[0]['serial']);
    }

    public function test_hsgq_epon_deny_commands_scan_configured_pons_in_one_session(): void
    {
        $groups = $this->callPrivateCommandBuilder('utilityListCommandGroups', [
            new OltDevice([
                'protocol_profile' => 'hsgq_epon',
                'pon_ports' => '7,8',
            ]),
            'deny',
        ]);

        $this->assertSame([
            [
                'label' => 'All configured PON deny candidates',
                'commands' => [
                    'config',
                    'interface epon 7',
                    'show blacklist onu-info all',
                    'exit',
                    'interface epon 8',
                    'show blacklist onu-info all',
                    'exit',
                ],
            ],
        ], $groups);
    }

    public function test_utility_commands_only_enter_enable_context(): void
    {
        $commands = $this->callPrivateCommandBuilder('utilityContextCommands', [
            ['enable', 'config', 'interface epon 1'],
        ]);

        $this->assertSame(['enable'], $commands);
    }

    private function callPrivateCommandBuilder(string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod(OltOnuController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(new OltOnuController(), $arguments);
    }

    private function onuData(): array
    {
        return [
            'pon_port' => 1,
            'onu_id' => 5,
            'serial' => 'HWTC12345678',
            'name' => 'Customer 5',
            'vlan' => 100,
            'ethernet_port' => 1,
        ];
    }
}
