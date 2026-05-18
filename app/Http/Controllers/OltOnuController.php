<?php

namespace App\Http\Controllers;

use App\Models\OltDevice;
use App\Models\OltOnu;
use App\Services\OltLiveOutputParser;
use App\Services\OltSshClient;
use App\Services\OltTelnetClient;
use App\Support\Utf8Text;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class OltOnuController extends Controller
{
    private const OLT_PROTOCOL_PROFILES = [
        'hsgq_epon' => 'HSGQ EPON OLT',
        'generic_epon' => 'Generic EPON OLT',
    ];

    public function index(Request $request)
    {
        $query = OltOnu::query()->with('oltDevice');

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('mac_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('olt_device_id')) {
            $query->where('olt_device_id', (int) $request->query('olt_device_id'));
        }

        if ($request->filled('pon_port')) {
            $query->where('pon_port', (int) $request->query('pon_port'));
        }

        if ($request->filled('vlan')) {
            $vlan = (int) $request->query('vlan');
            $query->where('port_vlans', 'like', '%"vlan":'.$vlan.'%');
        }

        if ($request->query('power') === 'missing') {
            $query->whereNull('rx_power_dbm');
        } elseif ($request->query('power') === 'weak') {
            $query->whereNotNull('rx_power_dbm')->where('rx_power_dbm', '<=', -25);
        }

        $onus = $query
            ->orderBy('pon_port')
            ->orderBy('onu_id')
            ->paginate($this->perPage($request, 1500, [50, 100, 200, 500, 1000, 1500]))
            ->appends($request->query());

        $stats = [
            'total' => OltOnu::count(),
            'with_power' => OltOnu::whereNotNull('rx_power_dbm')->count(),
            'weak_power' => OltOnu::whereNotNull('rx_power_dbm')->where('rx_power_dbm', '<=', -25)->count(),
            'last_polled_at' => OltOnu::max('last_live_polled_at'),
        ];

        $oltDevices = OltDevice::query()->orderBy('name')->get();
        $ponPorts = OltOnu::query()->select('pon_port')->distinct()->orderBy('pon_port')->pluck('pon_port');

        $perPageDefault = 1500;
        $perPageOptions = [50, 100, 200, 500, 1000, 1500];
        $protocolProfiles = self::OLT_PROTOCOL_PROFILES;

        return view('olt_onus.index', compact('onus', 'stats', 'ponPorts', 'oltDevices', 'perPageDefault', 'perPageOptions', 'protocolProfiles'));
    }

    public function createOlt()
    {
        return view('olt_onus.create_olt', [
            'oltDevice' => new OltDevice([
                'name' => 'US_EPON',
                'brand' => 'HSGQ',
                'protocol_profile' => 'hsgq_epon',
                'host' => '192.168.10.111',
                'access_method' => 'ssh',
                'port' => 22,
                'username' => 'isp_app',
                'read_context_commands' => "enable\nconfig",
                'pon_ports' => '1,2,3,4,5,6,7,8',
                'onu_status_command' => 'show onu-info all',
                'onu_power_command' => 'show optical-info',
                'onu_alarm_command' => 'show onu-info-alarm {onu_id}',
                'onu_vlan_command' => 'show port-vlan',
                'status' => 'active',
                'notes' => 'Read-only polling only. Do not use set/add/delete/save/reboot commands.',
            ]),
            'protocolProfiles' => self::OLT_PROTOCOL_PROFILES,
        ]);
    }

    public function storeOlt(Request $request)
    {
        $data = $this->validateOlt($request);

        OltDevice::create($data);

        return redirect()->route('olt-onus.index')->with('success', 'OLT added successfully. Now refresh live data.');
    }

    public function editOlt(OltDevice $oltDevice)
    {
        return view('olt_onus.edit_olt', [
            'oltDevice' => $oltDevice,
            'protocolProfiles' => self::OLT_PROTOCOL_PROFILES,
        ]);
    }

    public function updateOlt(Request $request, OltDevice $oltDevice)
    {
        $data = $this->validateOlt($request, $oltDevice);

        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        if (($data['enable_password'] ?? null) === null) {
            unset($data['enable_password']);
        }

        $oltDevice->update($data);

        return redirect()->route('olt-onus.index')->with('success', 'OLT settings updated successfully.');
    }

    public function refresh(OltDevice $oltDevice, OltSshClient $sshClient, OltTelnetClient $telnetClient, OltLiveOutputParser $parser)
    {
        if ($oltDevice->status !== 'active') {
            return redirect()->route('olt-onus.index')->with('warning', 'OLT is inactive. Activate it before live refresh.');
        }

        $contextCommands = $this->contextCommands($oltDevice->read_context_commands);
        $blockedContextCommand = $this->firstUnsafeContextCommand($contextCommands);

        if ($blockedContextCommand) {
            return redirect()->route('olt-onus.index')->with('error', 'Unsafe OLT context command blocked. Only enable/config/interface epon/exit navigation is allowed: '.$blockedContextCommand);
        }

        $ponPorts = $this->ponPorts($oltDevice->pon_ports);

        if ($ponPorts === []) {
            return redirect()->route('olt-onus.index')->with('error', 'Invalid PON ports. Use comma separated numbers from 1 to 8.');
        }

        $showCommands = [
            $oltDevice->onu_status_command,
            $oltDevice->onu_power_command,
        ];

        if ($oltDevice->onu_alarm_command) {
            $showCommands[] = str_replace('{onu_id}', '1', $oltDevice->onu_alarm_command);
        }

        if ($oltDevice->onu_vlan_command) {
            $showCommands[] = $oltDevice->onu_vlan_command;
        }

        $blockedCommand = $this->firstUnsafeShowCommand($showCommands);

        if ($blockedCommand) {
            return redirect()->route('olt-onus.index')->with('error', 'Unsafe OLT command blocked. Only read-only show/display commands are allowed: '.$blockedCommand);
        }

        $client = $oltDevice->access_method === 'telnet' ? $telnetClient : $sshClient;

        try {
            if ($client instanceof OltTelnetClient) {
                $client->connect(
                    $oltDevice->host,
                    $oltDevice->port,
                    $oltDevice->username,
                    $oltDevice->password,
                    $oltDevice->enable_password
                );
            } else {
                $client->connect(
                    $oltDevice->host,
                    $oltDevice->port,
                    $oltDevice->username,
                    $oltDevice->password
                );
            }

            foreach ($this->baseContextCommands($contextCommands) as $contextCommand) {
                $client->command($contextCommand);
            }

            $outputs = [];

            foreach ($ponPorts as $ponPort) {
                $client->command("interface epon {$ponPort}");
                $statusOutput = $client->command($oltDevice->onu_status_command);
                $outputs[] = $statusOutput;
                $outputs[] = $client->command($oltDevice->onu_power_command);

                foreach ($this->alarmCommandsForPort($oltDevice->onu_alarm_command, $parser->parse($statusOutput), $ponPort) as $alarmCommand) {
                    $outputs[] = $client->command($alarmCommand);
                }

                foreach ($this->onuIdsMissingVlans($parser->parse($statusOutput), $oltDevice, $ponPort) as $onuId) {
                    if (! $oltDevice->onu_vlan_command || $oltDevice->protocol_profile !== 'hsgq_epon') {
                        continue;
                    }

                    $client->command("interface onu {$ponPort}/{$onuId}");
                    $outputs[] = $client->command($oltDevice->onu_vlan_command);
                    $client->command('exit');
                }
            }

            $client->close();

            $output = implode("\n", $outputs);
            $records = $parser->parse($output);
            $polledAt = now();

            foreach ($records as $record) {
                $this->updateLiveOnuRecord($oltDevice, $record, $polledAt);
            }

            $oltDevice->update([
                'last_polled_at' => $polledAt,
                'last_error' => null,
                'last_raw_output' => $output,
            ]);

            return redirect()
                ->route('olt-onus.index', ['olt_device_id' => $oltDevice->id])
                ->with('success', count($records).' live ONU record(s) refreshed from '.$oltDevice->name.'.');
        } catch (Throwable $exception) {
            $client->close();

            $error = Utf8Text::clean($exception->getMessage()) ?? 'Unknown error';

            $oltDevice->update([
                'last_error' => $error,
                'last_polled_at' => now(),
            ]);

            return redirect()->route('olt-onus.index')->with('error', 'OLT live refresh failed: '.$error);
        }
    }

    private function contextCommands(?string $commands): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/', (string) $commands) ?: []),
            fn (string $command): bool => $command !== ''
        ));
    }

    private function baseContextCommands(array $commands): array
    {
        return array_values(array_filter(
            $commands,
            fn (string $command): bool => ! preg_match('/^interface\s+epon\s+[1-8]$/', strtolower(trim($command)))
        ));
    }

    private function ponPorts(?string $ports): array
    {
        $values = preg_split('/[,\s]+/', (string) $ports) ?: [];
        $ports = [];

        foreach ($values as $value) {
            if ($value === '') {
                continue;
            }

            if (! ctype_digit($value)) {
                return [];
            }

            $port = (int) $value;

            if ($port < 1 || $port > 8) {
                return [];
            }

            $ports[] = $port;
        }

        return array_values(array_unique($ports));
    }

    private function firstUnsafeContextCommand(array $commands): ?string
    {
        foreach ($commands as $command) {
            $normalized = strtolower(trim($command));

            if (preg_match('/[;&|`$]/', $normalized)) {
                return $command;
            }

            if (
                $normalized === 'enable'
                || $normalized === 'config'
                || $normalized === 'configure'
                || $normalized === 'exit'
                || preg_match('/^interface\s+epon\s+[1-8]$/', $normalized)
            ) {
                continue;
            }

            return $command;
        }

        return null;
    }

    private function validateOlt(Request $request, ?OltDevice $oltDevice = null): array
    {
        $passwordRules = $oltDevice ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'protocol_profile' => ['required', Rule::in(array_keys(self::OLT_PROTOCOL_PROFILES))],
            'host' => ['required', 'string', 'max:255'],
            'access_method' => ['required', Rule::in(['ssh', 'telnet'])],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => $passwordRules,
            'enable_password' => ['nullable', 'string', 'max:255'],
            'read_context_commands' => ['nullable', 'string'],
            'pon_ports' => ['required', 'string', 'max:255'],
            'onu_status_command' => ['required', 'string', 'max:255'],
            'onu_power_command' => ['required', 'string', 'max:255'],
            'onu_alarm_command' => ['nullable', 'string', 'max:255'],
            'onu_vlan_command' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function firstUnsafeShowCommand(array $commands): ?string
    {
        foreach ($commands as $command) {
            $normalized = strtolower(trim((string) $command));

            if (! preg_match('/^(show|display)\b/', $normalized)) {
                return $command;
            }

            if (preg_match('/[;&|`$]/', $normalized)) {
                return $command;
            }

            if (preg_match('/\b(config|configure|set|add|delete|remove|bind|unbind|write|save|reboot|reset|shutdown|enable|erase|clear|reload|debug)\b/', $normalized)) {
                return $command;
            }
        }

        return null;
    }

    private function updateLiveOnuRecord(OltDevice $oltDevice, array $record, $polledAt): void
    {
        $onu = OltOnu::query()
            ->where('olt_device_id', $oltDevice->id)
            ->where('pon_port', $record['pon_port'])
            ->where('onu_id', $record['onu_id'])
            ->first();

        $onu ??= OltOnu::query()
            ->whereNull('olt_device_id')
            ->where('pon_port', $record['pon_port'])
            ->where('onu_id', $record['onu_id'])
            ->first();

        $values = [
            'olt_device_id' => $oltDevice->id,
            'olt_name' => $oltDevice->name,
            'last_live_polled_at' => $polledAt,
        ];

        foreach ([
            'mac_address',
            'status',
            'name',
            'description',
            'rx_power_dbm',
            'distance_m',
            'raw_live_output',
            'raw_interface_config',
            'port_vlans',
            'last_registered_at',
            'last_deregistered_at',
            'last_deregister_reason',
        ] as $field) {
            if (array_key_exists($field, $record)) {
                $values[$field] = $record[$field];
            }
        }

        if ($onu) {
            $onu->update($values);

            return;
        }

        OltOnu::create($values + [
            'pon_port' => $record['pon_port'],
            'onu_id' => $record['onu_id'],
        ]);
    }

    private function alarmCommandsForPort(?string $commandTemplate, array $records, int $ponPort): array
    {
        if (! $commandTemplate || ! str_contains($commandTemplate, '{onu_id}')) {
            return [];
        }

        $onuIds = [];

        foreach ($records as $record) {
            if (($record['pon_port'] ?? null) === $ponPort && isset($record['onu_id'])) {
                $onuIds[] = (int) $record['onu_id'];
            }
        }

        return array_map(
            fn (int $onuId): string => str_replace('{onu_id}', (string) $onuId, $commandTemplate),
            array_values(array_unique($onuIds))
        );
    }

    private function onuIdsMissingVlans(array $records, OltDevice $oltDevice, int $ponPort): array
    {
        $onuIds = [];

        foreach ($records as $record) {
            if (($record['pon_port'] ?? null) !== $ponPort || ! isset($record['onu_id'])) {
                continue;
            }

            $hasVlan = OltOnu::query()
                ->where('olt_device_id', $oltDevice->id)
                ->where('pon_port', $ponPort)
                ->where('onu_id', $record['onu_id'])
                ->whereNotNull('port_vlans')
                ->where('port_vlans', '!=', '[]')
                ->exists();

            if (! $hasVlan) {
                $onuIds[] = (int) $record['onu_id'];
            }
        }

        return array_values(array_unique($onuIds));
    }
}
