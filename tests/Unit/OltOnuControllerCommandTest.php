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

    private function callPrivateCommandBuilder(string $method, array $arguments): array
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
