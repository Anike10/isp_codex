<?php

namespace App\Http\Controllers;

use App\Jobs\RunOltFullRefresh;
use App\Models\OltDevice;
use App\Models\OltOnu;
use App\Models\OltProtocolProfile;
use App\Models\OltRefreshRun;
use App\Services\OltLiveOutputParser;
use App\Services\OltSnmpClient;
use App\Services\OltSshClient;
use App\Services\OltTelnetClient;
use App\Support\Utf8Text;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class OltOnuController extends Controller
{
    private const BUILT_IN_PROTOCOL_PROFILE_OPTIONS = [
        'hsgq_epon' => 'HSGQ EPON OLT',
        'hsgq_gpon' => 'HSGQ GPON OLT',
    ];

    public function index(Request $request)
    {
        $query = OltOnu::query()
            ->select([
                'id',
                'olt_device_id',
                'olt_name',
                'pon_port',
                'onu_id',
                'mac_address',
                'onu_type',
                'status',
                'name',
                'description',
                'note',
                'port_vlans',
                'port_admin_states',
                'ethernet_port_count',
                'learned_macs',
                'rx_power_dbm',
                'tx_power_dbm',
                'last_live_polled_at',
                'last_registered_at',
                'last_deregistered_at',
                'last_deregister_reason',
            ])
            ->with('oltDevice:id,name,protocol_profile');

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

        $this->applyOnuSort($query, $request);

        $statsQuery = $query->clone();

        $perPageDefault = 200;
        $perPageOptions = [50, 100, 200, 500, 1000];
        $perPage = $this->perPage($request, $perPageDefault, $perPageOptions);

        $onus = $query
            ->paginate($perPage)
            ->appends($request->query());

        $stats = [
            'total' => $statsQuery->count(),
            'with_power' => $statsQuery->clone()->whereNotNull('rx_power_dbm')->count(),
            'weak_power' => $statsQuery->clone()->whereNotNull('rx_power_dbm')->where('rx_power_dbm', '<=', -25)->count(),
            'last_polled_at' => $statsQuery->max('last_live_polled_at'),
        ];

        $oltDevices = OltDevice::query()
            ->with('latestRefreshRun')
            ->withCount([
                'onus',
                'onus as online_onus_count' => fn ($builder) => $builder->whereIn('status', ['online', 'active']),
            ])
            ->orderBy('name')
            ->get();
        $oltPonPorts = $oltDevices
            ->mapWithKeys(fn (OltDevice $oltDevice): array => [$oltDevice->id => $this->refreshPonOptions($oltDevice)])
            ->all();
        $ponPortQuery = OltOnu::query();

        if ($request->filled('olt_device_id')) {
            $ponPortQuery->where('olt_device_id', (int) $request->query('olt_device_id'));
        }

        $ponPorts = $ponPortQuery->select('pon_port')->distinct()->orderBy('pon_port')->pluck('pon_port');
        $oltPonSummaries = OltOnu::query()
            ->whereIn('olt_device_id', $oltDevices->pluck('id'))
            ->selectRaw('olt_device_id, pon_port, count(*) as total')
            ->groupBy('olt_device_id', 'pon_port')
            ->orderBy('pon_port')
            ->get()
            ->groupBy('olt_device_id');
        $oltCommandWarnings = $oltDevices
            ->mapWithKeys(fn (OltDevice $oltDevice): array => [$oltDevice->id => $this->profileCommandMismatches($oltDevice)])
            ->all();

        $protocolProfiles = $this->protocolProfileOptions();

        $powerHistory = app(\App\Services\OnuPowerHistoryService::class);
        $powerHistoryIntervalHours = $powerHistory->intervalHours();
        $powerHistoryRetentionDays = $powerHistory->retentionDays();
        $powerHistoryShowRx = $powerHistory->showRx();
        $powerHistoryShowTx = $powerHistory->showTx();

        return view('olt_onus.index', compact('onus', 'stats', 'ponPorts', 'oltPonPorts', 'oltPonSummaries', 'oltCommandWarnings', 'oltDevices', 'perPageDefault', 'perPageOptions', 'perPage', 'protocolProfiles', 'powerHistoryIntervalHours', 'powerHistoryRetentionDays', 'powerHistoryShowRx', 'powerHistoryShowTx'));
    }

    /** Sampling interval + retention for the party-page ONU signal graph. */
    public function updatePowerHistorySettings(Request $request, \App\Services\OnuPowerHistoryService $history)
    {
        $data = $request->validate([
            'interval_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $history->setIntervalHours($data['interval_hours']);
        $history->setRetentionDays($data['retention_days']);
        $history->setShowRx($request->boolean('show_rx'));
        $history->setShowTx($request->boolean('show_tx'));

        if ($request->input('action') === 'capture') {
            $result = $history->capture();
            $history->prune();

            return back()->with('success', "ONU signal settings saved. Captured {$result['sampled']} reading(s) now.");
        }

        return back()->with('success', 'ONU signal history settings saved.');
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
                'snmp_enabled' => false,
                'snmp_version' => '2c',
                'snmp_port' => 161,
                'snmp_timeout_ms' => 800,
                'snmp_retries' => 1,
                'snmp_power_divisor' => 1,
                'read_context_commands' => "enable\nconfig",
                'pon_ports' => '1,2,3,4,5,6,7,8',
                'onu_status_command' => 'show onu-info all',
                'onu_power_command' => 'show optical-info',
                'onu_alarm_command' => 'show onu-info-alarm {onu_id}',
                'onu_vlan_command' => 'show port-vlan',
                'onu_mac_command' => 'show mac-address epon all',
                'status' => 'active',
                'notes' => 'Read-only polling only. Do not use set/add/delete/save/reboot commands.',
            ]),
            'protocolProfiles' => $this->protocolProfileOptions(),
            'profileDefaults' => $this->protocolProfileDefaults(),
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
            'protocolProfiles' => $this->protocolProfileOptions(),
            'profileDefaults' => $this->protocolProfileDefaults(),
        ]);
    }

    public function updateOlt(Request $request, OltDevice $oltDevice)
    {
        $data = $this->validateOlt($request, $oltDevice);

        if ($oltDevice->protocol_profile !== $data['protocol_profile']) {
            $profile = OltProtocolProfile::query()->where('key', $data['protocol_profile'])->firstOrFail();
            $data = array_merge($data, $this->profileDefaultValues($profile));
        }

        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        if (($data['enable_password'] ?? null) === null) {
            unset($data['enable_password']);
        }

        if (($data['snmp_community'] ?? null) === null) {
            unset($data['snmp_community']);
        }

        $oltDevice->update($data);

        if ($oltDevice->status !== 'active') {
            return redirect()->route('olt-onus.index')
                ->with('success', 'OLT settings updated. Connection test was skipped because this OLT is inactive.');
        }

        try {
            $this->testOltConnection($oltDevice);
        } catch (Throwable $exception) {
            return redirect()->route('olt-onus.index')
                ->with('error', 'OLT settings were saved, but the connection test failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error'));
        }

        return redirect()->route('olt-onus.index')
            ->with('success', 'OLT settings updated and connection verified successfully.');
    }

    public function downloadConfigBackup(OltDevice $oltDevice)
    {
        try {
            $output = $this->runOltReadCommands($oltDevice, ['show running-config']);

            if ($this->hasOltCommandError($output)) {
                throw new \RuntimeException($this->summarizeOltOutput($output));
            }
        } catch (Throwable $exception) {
            return back()->with('error', 'OLT config backup failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error'));
        }

        $safeName = Str::slug($oltDevice->name, '_') ?: 'olt';
        $filename = $safeName.'_config_backup_'.now()->format('Ymd_His').'.txt';
        $backup = implode("\n", [
            '# OLT configuration backup',
            '# Name: '.$oltDevice->name,
            '# Host: '.$oltDevice->host.':'.$oltDevice->port,
            '# Brand/Profile: '.($oltDevice->brand ?: 'N/A').' / '.($oltDevice->protocol_profile ?: 'N/A'),
            '# Generated: '.now()->toDateTimeString(),
            '',
            trim($output),
            '',
        ]);

        return response()->streamDownload(
            static function () use ($backup): void {
                echo $backup;
            },
            $filename,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store, private',
            ]
        );
    }

    public function applyProfileDefaults(OltDevice $oltDevice)
    {
        $profile = $this->protocolProfile($oltDevice);

        if (! $profile) {
            return back()->with('error', 'The selected OLT profile no longer exists. Select a valid profile first.');
        }

        $oltDevice->update($this->profileDefaultValues($profile));

        return redirect()->route('olt-onus.index', ['olt_device_id' => $oltDevice->id])
            ->with('success', $profile->label.' polling defaults applied to '.$oltDevice->name.'. Run refresh when you are ready.');
    }

    public function clearCachedOnus(OltDevice $oltDevice)
    {
        $oltDevice->update([
            'last_error' => null,
            'last_raw_output' => null,
        ]);

        return redirect()->route('olt-onus.index', ['olt_device_id' => $oltDevice->id])
            ->with('success', 'Refresh error/output cleared for '.$oltDevice->name.'. Cached ONU/ONT rows were kept.');
    }

    public function destroyOlt(OltDevice $oltDevice)
    {
        $name = $oltDevice->name;
        $deletedOnus = 0;

        DB::transaction(function () use ($oltDevice, &$deletedOnus): void {
            $deletedOnus = $oltDevice->onus()->delete();
            $oltDevice->delete();
        });

        return redirect()->route('olt-onus.index')
            ->with('success', 'OLT '.$name.' deleted with '.$deletedOnus.' cached ONU/ONT row(s).');
    }

    public function protocolProfiles()
    {
        return view('olt_onus.protocol_profiles.index', [
            'profiles' => OltProtocolProfile::query()->orderBy('label')->get(),
        ]);
    }

    public function createProtocolProfile()
    {
        return view('olt_onus.protocol_profiles.create', [
            'profile' => new OltProtocolProfile([
                'pon_interface_command' => 'interface epon {pon_port}',
            ]),
        ]);
    }

    public function storeProtocolProfile(Request $request)
    {
        OltProtocolProfile::create($this->validateProtocolProfile($request));

        return redirect()->route('olt-onus.protocol-profiles.index')->with('success', 'OLT protocol/profile added.');
    }

    public function editProtocolProfile(OltProtocolProfile $oltProtocolProfile)
    {
        return view('olt_onus.protocol_profiles.edit', [
            'profile' => $oltProtocolProfile,
        ]);
    }

    public function updateProtocolProfile(Request $request, OltProtocolProfile $oltProtocolProfile)
    {
        $oltProtocolProfile->update($this->validateProtocolProfile($request, $oltProtocolProfile));

        return redirect()->route('olt-onus.protocol-profiles.index')->with('success', 'OLT protocol/profile updated.');
    }

    public function destroyProtocolProfile(OltProtocolProfile $oltProtocolProfile)
    {
        if (in_array($oltProtocolProfile->key, ['hsgq_epon', 'hsgq_gpon', 'generic_epon'], true)) {
            return back()->withErrors(['profile' => 'Built-in OLT protocol profiles cannot be deleted.']);
        }

        if (OltDevice::query()->where('protocol_profile', $oltProtocolProfile->key)->exists()) {
            return back()->withErrors(['profile' => 'This profile is assigned to an OLT. Change that OLT profile before deleting it.']);
        }

        $oltProtocolProfile->delete();

        return redirect()->route('olt-onus.protocol-profiles.index')->with('success', 'OLT protocol/profile deleted.');
    }

    public function updateVlan(Request $request, OltOnu $oltOnu)
    {
        $request->merge(['mode' => $request->input('mode', 'tag')]);
        $data = $request->validate([
            'mode' => ['required', Rule::in(['tag', 'transparent'])],
            'vlan' => ['nullable', 'required_if:mode,tag', 'integer', 'min:1', 'max:4094'],
        ]);

        $mode = $data['mode'];
        $vlan = $mode === 'tag' ? (int) $data['vlan'] : null;
        $portVlans = $oltOnu->port_vlans ?: [['port' => 1, 'mode' => 'manual', 'priority' => null]];
        $profile = $oltOnu->oltDevice ? $this->protocolProfile($oltOnu->oltDevice) : null;

        if (! $oltOnu->oltDevice) {
            $message = 'This ONU is not linked with an editable OLT device.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        if (! $profile?->vlan_write_command) {
            $message = 'VLAN write command is not configured for '.$profile?->label.'. Set it from OLT Protocol/Profile first.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        if ($mode === 'transparent' && ! $this->usesHsgqEpon($oltOnu->oltDevice)) {
            $message = 'Transparent VLAN mode is currently supported only for HSGQ EPON ONU ports.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        try {
        $writeOutput = $mode === 'transparent'
            ? $this->writeOnuTransparentVlanToOlt($oltOnu, $profile, $portVlans)
            : $this->writeOnuVlanToOlt($oltOnu, $profile, $vlan, $portVlans);

        if ($profile->save_config_command) {
            $writeOutput .= "\n".$this->runOltWriteCommands($oltOnu->oltDevice, [$profile->save_config_command]);
        }
        } catch (Throwable $exception) {
            $message = 'OLT VLAN write failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error');

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        if ($mode === 'transparent') {
            $portVlans = array_map(function (array $portVlan): array {
                unset($portVlan['vlan']);
                $portVlan['mode'] = 'transparent';
                $portVlan['priority'] = null;

                return $portVlan;
            }, $portVlans);

            $oltOnu->update([
                'port_vlans' => $portVlans,
                'raw_interface_config' => trim(($oltOnu->raw_interface_config ?: '')."\nOLT VLAN mode changed to transparent from OLT ONU list at ".now()->format('d/m/Y H:i:s')."\n".$writeOutput),
            ]);

            $message = "OLT VLAN mode changed to transparent for {$oltOnu->pon_port}/{$oltOnu->onu_id} and saved permanently.";

            return $request->expectsJson()
                ? response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'onu' => $this->onuPayload($oltOnu->fresh()),
                ])
                : back()->with('success', $message);
        }

        if ($oltOnu->oltDevice->protocol_profile === 'hsgq_gpon' && $this->gponVlanPortPath($oltOnu, 1) === 'veip') {
            $liveVlans = collect($oltOnu->learned_macs ?: [])
                ->pluck('vlan')
                ->filter(fn ($liveVlan) => $liveVlan !== null && $liveVlan !== '')
                ->unique()
                ->implode(', ');

            $oltOnu->update([
                'raw_interface_config' => trim(($oltOnu->raw_interface_config ?: '')."\nOLT HGU VEIP native VLAN updated to {$vlan} at ".now()->format('d/m/Y H:i:s')."\n".$writeOutput),
            ]);

            $message = "OLT accepted and saved HGU VEIP native VLAN {$vlan} for {$oltOnu->pon_port}/{$oltOnu->onu_id}.";

            if ($liveVlans !== '' && ! collect(explode(', ', $liveVlans))->contains((string) $vlan)) {
                $message .= " Current HGU WAN traffic is still learned on VLAN {$liveVlans}; change the HGU WAN profile if that tagged traffic must move to VLAN {$vlan}.";
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => $message,
                    'onu' => $this->onuPayload($oltOnu->fresh()),
                ]);
            }

            return back()->with('warning', $message);
        }

        $portVlans = array_map(function (array $portVlan) use ($vlan): array {
            $portVlan['vlan'] = $vlan;
            $portVlan['mode'] = $portVlan['mode'] ?? 'manual';
            $portVlan['priority'] = $portVlan['priority'] ?? null;

            return $portVlan;
        }, $portVlans);

        $learnedMacs = array_map(function (array $learnedMac) use ($vlan): array {
            if (array_key_exists('vlan', $learnedMac)) {
                $learnedMac['vlan'] = $vlan;
            }

            return $learnedMac;
        }, $oltOnu->learned_macs ?: []);

        $oltOnu->update([
            'port_vlans' => $portVlans,
            'learned_macs' => $learnedMacs,
            'raw_interface_config' => trim(($oltOnu->raw_interface_config ?: '')."\nOLT VLAN updated to {$vlan} from OLT ONU list at ".now()->format('d/m/Y H:i:s')."\n".$writeOutput),
        ]);

        $message = "OLT VLAN changed to {$vlan} for {$oltOnu->pon_port}/{$oltOnu->onu_id} and saved permanently.";

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'onu' => $this->onuPayload($oltOnu->fresh()),
            ]);
        }

        return back()->with('success', $message);
    }

    public function updateEthernetPortState(Request $request, OltOnu $oltOnu)
    {
        $data = $request->validate([
            'ethernet_port' => ['required', 'integer', 'min:1', 'max:32'],
            'state' => ['required', Rule::in(['enable', 'disable'])],
        ]);
        $oltDevice = $oltOnu->oltDevice;
        $profile = $oltDevice ? $this->protocolProfile($oltDevice) : null;

        if (! $oltDevice) {
            return $request->expectsJson()
                ? response()->json(['message' => 'This ONU/ONT is not linked with an OLT device.'], 422)
                : back();
        }

        if ($oltDevice->status !== 'active') {
            return $request->expectsJson()
                ? response()->json(['message' => 'The OLT is inactive. Activate it before changing an ONU/ONT port.'], 422)
                : back();
        }

        if (! $profile || (! $this->usesHsgqEpon($oltDevice) && ! $profile->port_admin_command)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Ethernet port enable/disable command is not configured for '.$profile?->label.'.'], 422)
                : back();
        }

        $port = (int) $data['ethernet_port'];
        $state = (string) $data['state'];
        $commands = $this->ethernetPortStateCommands($oltOnu, $profile, $port, $state);

        try {
            $output = $this->runOltWriteCommands($oltDevice, $commands);
        } catch (Throwable $exception) {
            $message = 'OLT Ethernet port '.$state.' failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error');

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back();
        }

        $states = $oltOnu->port_admin_states ?: [];
        $states[(string) $port] = $state === 'enable' ? 'enabled' : 'disabled';
        $oltOnu->update([
            'port_admin_states' => $states,
            'raw_interface_config' => trim(($oltOnu->raw_interface_config ?: '')."\nEthernet port {$port} {$state}d at ".now()->format('d/m/Y H:i:s')."\n".$output),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'port' => $port,
                'port_state' => $states[(string) $port],
            ]);
        }

        return back();
    }

    public function updateName(Request $request, OltOnu $oltOnu)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $desired = (string) $data['name'];
        $previousName = (string) $oltOnu->name;

        // Save immediately so UI reflects change
        $oltOnu->update(['name' => $desired]);

        $oltDevice = $oltOnu->oltDevice;

        if (! $oltDevice) {
            return back()->with('success', "ONU name updated to '{$desired}' for {$oltOnu->pon_port}/{$oltOnu->onu_id}.");
        }

        $parser = app(OltLiveOutputParser::class);
        $profile = $this->protocolProfile($oltDevice);

        $candidateCommandSets = [];

        // Use GPON repair commands first for GPON devices
        if (str_contains((string) $oltDevice->protocol_profile, 'gpon')) {
            $candidateCommandSets[] = $this->gponOnuNameRepairCommands([
                'pon_port' => $oltOnu->pon_port,
                'onu_id' => $oltOnu->onu_id,
                'name' => $desired,
            ]);
        }

        // EPON/other variants
        $candidateCommandSets[] = $this->eponOnuDescriptionCommands([
            'pon_port' => $oltOnu->pon_port,
            'onu_id' => $oltOnu->onu_id,
            'name' => $desired,
            'mac_address' => $oltOnu->mac_address,
            'onu_type' => $oltOnu->onu_type,
        ]);

        // simple modify commands
        $candidateCommandSets[] = [
            $this->ponInterfaceCommand($oltDevice, (int) $oltOnu->pon_port),
            "?ont modify {$oltOnu->pon_port} {$oltOnu->onu_id} name \"".str_replace('"', '', $desired).'\"',
            "?ont modify {$oltOnu->pon_port} {$oltOnu->onu_id} desc \"".str_replace('"', '', $desired).'\"',
            'exit',
        ];

        $matched = false;
        $truncatedReadback = null;
        $lastReadbackName = null;
        $aggregateOutput = [];

        foreach ($candidateCommandSets as $commands) {
            try {
                $commandsToRun = $commands;
                if ($profile?->save_config_command) {
                    $commandsToRun[] = $profile->save_config_command;
                }

                $writeOutput = $this->runOltWriteCommands($oltDevice, $commandsToRun);
                $aggregateOutput[] = $writeOutput;

                $record = $this->queryOnuRecordGeneric($oltDevice, $parser, (int) $oltOnu->pon_port, (int) $oltOnu->onu_id);

                if ($record) {
                    $liveName = trim((string) ($record['name'] ?? $record['description'] ?? ''));
                    if ($liveName !== '') {
                        $lastReadbackName = $this->cleanOltReadbackName($liveName);
                    }

                    if ($this->oltNamesMatch($liveName, $desired)) {
                        if ($liveName !== '') {
                            $cleanLiveName = $this->cleanOltReadbackName($liveName);
                            $oltOnu->update(['name' => $cleanLiveName]);
                            if ($this->oltNameReadbackWasTruncated($cleanLiveName, $desired)) {
                                $truncatedReadback = $cleanLiveName;
                            }
                        }

                        $matched = true;
                        break;
                    }
                }
            } catch (Throwable $exception) {
                $aggregateOutput[] = 'Write attempt failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error');
            }
        }

        if (! $matched) {
            $record = $this->queryOnuRecordGeneric($oltDevice, $parser, (int) $oltOnu->pon_port, (int) $oltOnu->onu_id);

            if ($record) {
                $liveName = trim((string) ($record['name'] ?? $record['description'] ?? ''));
                if ($liveName !== '') {
                    // overwrite app with OLT value
                    $lastReadbackName = $this->cleanOltReadbackName($liveName);
                    $oltOnu->update(['name' => $lastReadbackName]);
                }
            }

            if ($lastReadbackName === null) {
                $oltOnu->update(['name' => $previousName]);
            }

            $note = implode("\n---\n", array_filter($aggregateOutput));
            $oltOnu->update(['raw_interface_config' => trim(($oltOnu->raw_interface_config ?: '')."\nAttempted name writes at ".now()->format('d/m/Y H:i:s')."\n".$note)]);

            $lengthMessage = $lastReadbackName
                ? " Requested ".mb_strlen($desired)." characters, but OLT/readback returned '{$lastReadbackName}' (".mb_strlen($lastReadbackName).' characters).'
                : ' The OLT may not support a name this long ('.mb_strlen($desired).' characters).';
            $appStateMessage = $lastReadbackName
                ? ' Application now shows the value read from the OLT.'
                : ' No name could be read back, so the previous App name was restored.';

            return back()->with('error', 'OLT name save failed.'.$appStateMessage.$lengthMessage);
        }

        $oltDevice->update(['last_error' => null]);
        $oltOnu->update(['raw_interface_config' => trim(($oltOnu->raw_interface_config ?: '')."\nName written to OLT at ".now()->format('d/m/Y H:i:s')."\n".implode("\n", array_filter($aggregateOutput)))]);

        if ($truncatedReadback !== null) {
            return back()->with('warning', "OLT/readback did not support or return the full ONU name. Requested '{$desired}' (".mb_strlen($desired)." characters); saved/read back '{$truncatedReadback}' (".mb_strlen($truncatedReadback).' characters). The App now shows the actual OLT value.');
        }

        return back()->with('success', "ONU name updated to '{$desired}' for {$oltOnu->pon_port}/{$oltOnu->onu_id} and written to OLT.");
    }

    public function updateDescription(Request $request, OltOnu $oltOnu)
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $desired = $data['description'] ?? null;

        // Save immediately to application DB so UI reflects user's input quickly.
        $oltOnu->update(['description' => $desired]);

        $oltDevice = $oltOnu->oltDevice;

        if (! $oltDevice) {
            return back()->with('success', "ONU description updated for {$oltOnu->pon_port}/{$oltOnu->onu_id}.");
        }

        $parser = app(OltLiveOutputParser::class);
        $profile = $this->protocolProfile($oltDevice);

        $candidateCommandSets = [];

        // GPON-specific wide-reach repairs
        if (str_contains((string) $oltDevice->protocol_profile, 'gpon')) {
            $candidateCommandSets[] = $this->gponOnuNameRepairCommands([
                'pon_port' => $oltOnu->pon_port,
                'onu_id' => $oltOnu->onu_id,
                'name' => (string) ($desired ?? ''),
            ]);
        }

        // EPON / generic variants: try multiple possible command forms
        $candidateCommandSets[] = $this->eponOnuDescriptionCommands([
            'pon_port' => $oltOnu->pon_port,
            'onu_id' => $oltOnu->onu_id,
            'name' => (string) ($desired ?? ''),
            'mac_address' => $oltOnu->mac_address,
            'onu_type' => $oltOnu->onu_type,
        ]);

        // Also try a simple "interface + ont/onu set desc/name" attempt
        $candidateCommandSets[] = [
            $this->ponInterfaceCommand($oltDevice, (int) $oltOnu->pon_port),
            "?ont modify {$oltOnu->pon_port} {$oltOnu->onu_id} desc \"".str_replace('"', '', (string) ($desired ?? '')).'\"',
            "?ont modify {$oltOnu->pon_port} {$oltOnu->onu_id} name \"".str_replace('"', '', (string) ($desired ?? '')).'\"',
            'exit',
        ];

        $matched = false;
        $aggregateOutput = [];

        foreach ($candidateCommandSets as $commands) {
            try {
                // If profile defines a permanent save command, try it after write
                $commandsToRun = $commands;
                if ($profile?->save_config_command) {
                    $commandsToRun[] = $profile->save_config_command;
                }

                $writeOutput = $this->runOltWriteCommands($oltDevice, $commandsToRun);
                $aggregateOutput[] = $writeOutput;

                // Read back live record trying several common read commands
                $record = $this->queryOnuRecordGeneric($oltDevice, $parser, (int) $oltOnu->pon_port, (int) $oltOnu->onu_id);

                if ($record) {
                    $liveDesc = trim((string) ($record['description'] ?? $record['name'] ?? ''));

                    if ($liveDesc !== '') {
                        // Compare case-insensitively
                        if ($this->oltNamesMatch($liveDesc, (string) ($desired ?? ''))) {
                            $oltOnu->update(['description' => $this->cleanOltReadbackName($liveDesc)]);
                            $matched = true;
                            break;
                        }
                    }
                }
            } catch (Throwable $exception) {
                $aggregateOutput[] = 'Write attempt failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error');
            }
        }

        // If no candidate matched, fetch live and overwrite application value with OLT's value
        if (! $matched) {
            $record = $this->queryOnuRecordGeneric($oltDevice, $parser, (int) $oltOnu->pon_port, (int) $oltOnu->onu_id);

            if ($record) {
                $liveDesc = trim((string) ($record['description'] ?? $record['name'] ?? ''));

                if ($liveDesc !== '') {
                    // replace application value with actual OLT value so UI shows OLT state
                    $oltOnu->update(['description' => $liveDesc]);
                }
            }

            $note = implode("\n---\n", array_filter($aggregateOutput));

            // persist a small note to raw_interface_config for debugging
            $oltOnu->update(['raw_interface_config' => trim(($oltOnu->raw_interface_config ?: '')."\nAttempted description writes at ".now()->format('d/m/Y H:i:s')."\n".$note)]);

            return back()->with('error', 'OLT description save failed; application now shows the value read from the OLT.');
        }

        // Success: log outputs and return success
        $oltDevice->update(['last_error' => null]);
        $oltOnu->update(['raw_interface_config' => trim(($oltOnu->raw_interface_config ?: '')."\nDescription written to OLT at ".now()->format('d/m/Y H:i:s')."\n".implode("\n", array_filter($aggregateOutput)))]);

        return back()->with('success', "ONU description updated for {$oltOnu->pon_port}/{$oltOnu->onu_id} and written to OLT.");
    }

    public function saveOltConfig(OltDevice $oltDevice)
    {
        $profile = $this->protocolProfile($oltDevice);

        if (! $profile?->save_config_command) {
            return back()->with('error', 'Permanent save command is not configured for '.$profile?->label.'. Set it from OLT Protocol/Profile first.');
        }

        try {
            $output = $this->runOltWriteCommands($oltDevice, [$profile->save_config_command]);
        } catch (Throwable $exception) {
            return back()->with('error', 'OLT permanent save failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error'));
        }

        $oltDevice->update([
            'last_error' => null,
            'last_raw_output' => trim(($oltDevice->last_raw_output ?: '')."\n".$output),
        ]);

        return back()->with('success', $oltDevice->name.' config saved permanently.');
    }

    public function denyList(Request $request)
    {
        return $this->onuUtilityList($request, 'deny');
    }

    public function deleteDenyListEntry(Request $request)
    {
        $data = $request->validate([
            'olt_device_id' => ['required', 'integer', 'exists:olt_devices,id'],
            'pon_port' => ['required', 'integer', 'min:1', 'max:8'],
            'serial' => ['required', 'string', 'max:64', 'regex:/^(?:[0-9a-f]{2}[:-]){5}[0-9a-f]{2}$/i'],
        ]);

        $oltDevice = OltDevice::query()->findOrFail($data['olt_device_id']);

        if (! $this->usesHsgqEpon($oltDevice)) {
            return back()->with('error', 'Delete from deny list is currently supported only for HSGQ EPON OLT entries.');
        }

        $serial = strtolower(str_replace('-', ':', $data['serial']));
        $profile = $this->protocolProfile($oltDevice);

        try {
            $this->runOltWriteCommands(
                $oltDevice,
                $this->denyListDeleteCommands((int) $data['pon_port'], $serial, $profile)
            );
        } catch (Throwable $exception) {
            return back()->with('error', 'Deny entry delete failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error'));
        }

        return redirect()
            ->route('olt-onus.deny-list', ['olt_device_id' => $oltDevice->id])
            ->with('success', 'Deny entry '.$serial.' removed from PON '.$data['pon_port'].' and OLT config saved.');
    }

    public function autoDiscoveryList(Request $request)
    {
        return $this->onuUtilityList($request, 'discovery');
    }

    public function addDiscoveredOnu(Request $request, OltLiveOutputParser $parser)
    {
        $data = $request->validate([
            'olt_device_id' => ['required', 'exists:olt_devices,id'],
            'pon_port' => ['required', 'integer', 'min:1', 'max:16'],
            'onu_id' => ['nullable', 'integer', 'min:0', 'max:256'],
            'serial' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'vlan' => ['required', 'integer', 'min:1', 'max:4094'],
            'ethernet_port' => ['required', 'integer', 'min:1', 'max:8'],
            'source_type' => ['nullable', 'in:deny,discovery,manual'],
        ]);

        $oltDevice = OltDevice::findOrFail($data['olt_device_id']);
        $profile = $this->protocolProfile($oltDevice);
        $existingOnus = null;
        $existingOnu = null;
        $autoAssignEponOnu = $this->usesHsgqEpon($oltDevice) && (int) ($data['onu_id'] ?? -1) === 0;

        if (! $autoAssignEponOnu && $oltDevice->protocol_profile !== 'hsgq_gpon' && (int) ($data['onu_id'] ?? -1) === 0) {
            $data['onu_id'] = null;
        }

        if ($oltDevice->protocol_profile === 'hsgq_gpon') {
            try {
                $existingOnus = $this->fetchExistingGponOnus($oltDevice, $parser);
                $existingOnu = $this->findExistingGponOnuBySerial($existingOnus, $data['serial']);
            } catch (Throwable $exception) {
                $existingOnus = null;
                $existingOnu = null;
            }
        }

        if ($existingOnu) {
            $data['onu_id'] = $existingOnu['onu_id'];
            $commands = $this->authorizeExistingGponOnuCommands($oltDevice, $profile, $data);
        } elseif ($autoAssignEponOnu) {
            $data['onu_id'] = 0;
            $commands = $this->authorizeAutoEponOnuCommands($data);
        } else {
            $data['onu_id'] = $this->resolveOnuId($oltDevice, (int) $data['pon_port'], $data['onu_id'] ?? null, $existingOnus);
            $commands = $this->authorizeOnuCommands($oltDevice, $profile, $data);
        }

        $nameRepair = ['name' => null, 'matched' => false, 'output' => ''];

        try {
            $output = $this->runOltWriteCommands($oltDevice, $commands);

            if ($oltDevice->protocol_profile === 'hsgq_gpon') {
                $nameRepair = $this->verifyAndRepairGponOnuName($oltDevice, $parser, $data);
                $output = trim($output."\n".$nameRepair['output']);
            }

            if ($autoAssignEponOnu) {
                $assignedOnuId = $this->findEponOnuIdInOutput($output, $oltDevice, $data['serial']);

                if ($assignedOnuId === null) {
                    $readOutput = $this->runOltUtilityCommands($oltDevice, [
                        'config',
                        'interface epon '.(int) $data['pon_port'],
                        'show onu-info all',
                        'exit',
                    ]);
                    $output = trim($output."\n".$readOutput);
                    $assignedOnuId = $this->findEponOnuIdInOutput($readOutput, $oltDevice, $data['serial']);
                }

                if ($assignedOnuId === null) {
                    throw new \RuntimeException('OLT accepted auto bind, but assigned ONU ID was not found in show onu-info output.');
                }

                $data['onu_id'] = $assignedOnuId;
                $vlanOutput = $this->runOltWriteCommands($oltDevice, $this->eponVlanCommands($profile, $data));
                $output = trim($output."\n".$vlanOutput);
            }
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'ONU add failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error'));
        }

        OltOnu::updateOrCreate(
            [
                'olt_device_id' => $oltDevice->id,
                'pon_port' => (int) $data['pon_port'],
                'onu_id' => (int) $data['onu_id'],
            ],
            [
                'olt_name' => $oltDevice->name,
                'mac_address' => $data['serial'],
                'name' => $nameRepair['name'] ?: $data['name'],
                'description' => $data['description'] ?? null,
                'status' => 'authorized',
                'port_vlans' => [[
                    'port' => (int) $data['ethernet_port'],
                    'mode' => 'tag',
                    'vlan' => (int) $data['vlan'],
                    'priority' => 0,
                ]],
                'raw_interface_config' => trim("Added from ".($data['source_type'] ?? 'auto discovery').' at '.now()->format('d/m/Y H:i:s')."\n".$output),
                'last_live_polled_at' => now(),
            ]
        );

        $message = $existingOnu
            ? 'Existing ONU '.$data['pon_port'].'/'.$data['onu_id'].' updated, VLAN '.$data['vlan'].' configured, and OLT config saved.'
            : 'ONU '.$data['pon_port'].'/'.$data['onu_id'].' added, VLAN '.$data['vlan'].' configured, and OLT config saved.';

        if ($oltDevice->protocol_profile === 'hsgq_gpon' && ! $nameRepair['matched']) {
            $message .= ' OLT name did not confirm as requested; actual OLT name is '.($nameRepair['name'] ?: 'unknown').'.';
        }

        $redirectRoute = ($data['source_type'] ?? null) === 'deny'
            ? 'olt-onus.deny-list'
            : 'olt-onus.auto-discovery';

        return redirect()->route($redirectRoute, ['olt_device_id' => $oltDevice->id])
            ->with('success', $message);
    }

    public function refresh(Request $request, OltDevice $oltDevice, OltSshClient $sshClient, OltTelnetClient $telnetClient, OltLiveOutputParser $parser, string $redirectRoute = 'olt-onus.index', array $redirectParams = [])
    {
        if (in_array($request->input('refresh_mode'), ['full', 'full_mac'], true) && ! $request->boolean('_background_worker')) {
            return $this->startBackgroundRefresh($request, $oltDevice);
        }

        $startedAt = microtime(true);

        $this->updateRefreshProgress(5, 'Checking OLT settings', 'running');

        if ($oltDevice->status !== 'active') {
            $this->failRefreshProgress('OLT is inactive. Activate it before live refresh.');
            return redirect()->route($redirectRoute, $redirectParams ?: ['olt_device_id' => $oltDevice->id])->with('warning', 'OLT is inactive. Activate it before live refresh.');
        }

        $contextCommands = $this->contextCommands($oltDevice->read_context_commands);
        $blockedContextCommand = $this->firstUnsafeContextCommand($contextCommands);

        if ($blockedContextCommand) {
            $this->failRefreshProgress('Unsafe OLT context command: '.$blockedContextCommand);
            return redirect()->route($redirectRoute, $redirectParams ?: ['olt_device_id' => $oltDevice->id])->with('error', 'Unsafe OLT context command blocked. Only enable/config/interface epon/interface gpon/exit navigation is allowed: '.$blockedContextCommand);
        }

        $ponPorts = $this->ponPorts($oltDevice->pon_ports);

        if ($ponPorts === []) {
            $this->failRefreshProgress('Invalid PON port configuration.');
            return redirect()->route($redirectRoute, $redirectParams ?: ['olt_device_id' => $oltDevice->id])->with('error', 'Invalid PON ports. Use comma separated numbers from 1 to 8.');
        }

        $selectedPonPort = $this->selectedPonPort($this->refreshPonOptions($oltDevice));

        if ($selectedPonPort !== null) {
            $ponPorts = [$selectedPonPort];
            $redirectParams = array_merge($redirectParams, ['pon_port' => $selectedPonPort]);
        }

        $profile = $this->protocolProfile($oltDevice);
        $statusCommand = $this->normalizeOltStatusCommand($oltDevice->onu_status_command ?: $profile?->default_onu_status_command);
        $powerCommand = $oltDevice->onu_power_command ?: $profile?->default_onu_power_command;
        $alarmCommand = $oltDevice->onu_alarm_command ?: $profile?->default_onu_alarm_command;
        $vlanCommand = $oltDevice->onu_vlan_command ?: $profile?->default_onu_vlan_command;
        $macCommand = $oltDevice->onu_mac_command ?: $profile?->default_onu_mac_command;

        $showCommands = [
            $statusCommand,
            $powerCommand,
        ];

        if ($alarmCommand) {
            $showCommands[] = str_replace('{onu_id}', '1', $alarmCommand);
        }

        if ($vlanCommand) {
            $showCommands[] = $vlanCommand;
        }

        if ($macCommand) {
            $showCommands[] = $macCommand;
        }

        $blockedCommand = $this->firstUnsafeShowCommand($showCommands);

        if ($blockedCommand) {
            $this->failRefreshProgress('Unsafe OLT polling command: '.$blockedCommand);
            return redirect()->route('olt-onus.index')->with('error', 'Unsafe OLT command blocked. Only read-only show/display commands are allowed: '.$blockedCommand);
        }

        $accessMethod = $this->readAccessMethod($oltDevice);
        $port = $accessMethod === 'telnet' && $oltDevice->access_method !== 'telnet'
            ? 23
            : (int) $oltDevice->port;
        $client = $accessMethod === 'telnet' ? $telnetClient : $sshClient;
        $refreshMode = request()->input('refresh_mode');
        $fullDetailRefresh = in_array($refreshMode, ['full', 'full_mac'], true);
        $fastInventoryRefresh = ! $fullDetailRefresh && $this->usesFastInventoryRefresh($oltDevice);
        $statusOnlyRefresh = ! $fullDetailRefresh && $this->usesStatusOnlyRefresh($oltDevice);
        $eponPowerVlanRefresh = $this->usesEponPowerVlanRefresh($oltDevice, $fullDetailRefresh);
        $readTimeout = $this->readTimeoutSeconds($oltDevice, $fullDetailRefresh);

        $connectClient = function () use ($client, $oltDevice, $port, $readTimeout): void {
            if ($client instanceof OltTelnetClient) {
                $client->connect(
                    $oltDevice->host,
                    $port,
                    $oltDevice->username,
                    $oltDevice->password,
                    $oltDevice->enable_password,
                    $readTimeout
                );

                return;
            }

            $client->connect(
                $oltDevice->host,
                $port,
                $oltDevice->username,
                $oltDevice->password
            );
        };

        $this->updateRefreshProgress(10, 'Connecting to '.$oltDevice->name);

        try {
            $connectClient();

            $this->updateRefreshProgress(20, 'Connected; reading OLT data');

            foreach ($this->baseContextCommands($contextCommands, $oltDevice) as $contextCommand) {
                $client->command($contextCommand);
            }

            $outputs = [];
            $authoritativeKeysByPon = [];

            if ($this->usesGlobalGponStatusRefresh($oltDevice, $statusCommand)) {
                $statusOutput = $client->command($statusCommand);

                if ($this->hasOltCommandError($statusOutput)) {
                    throw new \RuntimeException('OLT rejected status command: '.$this->summarizeOltOutput($statusOutput));
                }

                $statusRecords = $parser->parse($statusOutput);

                if ($statusRecords === []) {
                    throw new \RuntimeException('OLT returned no parseable ONU/ONT status rows. Existing cached rows were kept.');
                }

                $authoritativeKeysByPon = $this->onuRecordKeysGroupedByPon($statusRecords);
                $outputs[] = $statusOutput;
                $this->updateRefreshProgress(40, count($statusRecords).' ONU/ONT status rows received');

                if ($powerCommand) {
                    $outputs[] = $client->command($powerCommand);
                }
                $this->updateRefreshProgress(58, 'Optical power data received');

                if ($fullDetailRefresh && $oltDevice->protocol_profile === 'hsgq_gpon') {
                    $cachedCapabilities = OltOnu::query()
                        ->where('olt_device_id', $oltDevice->id)
                        ->get(['pon_port', 'onu_id', 'mac_address', 'ethernet_port_count'])
                        ->keyBy(fn (OltOnu $onu): string => $this->onuRecordKey($onu->pon_port, $onu->onu_id));
                    $capabilityRecords = collect($statusRecords)
                        ->filter(fn (array $record): bool => isset($record['pon_port'], $record['onu_id']))
                        ->filter(function (array $record) use ($cachedCapabilities): bool {
                            $cached = $cachedCapabilities->get($this->onuRecordKey((int) $record['pon_port'], (int) $record['onu_id']));

                            if (! $cached || $cached->ethernet_port_count === null) {
                                return true;
                            }

                            $liveSerial = strtolower(trim((string) ($record['mac_address'] ?? '')));
                            $cachedSerial = strtolower(trim((string) $cached->mac_address));

                            return $liveSerial !== '' && $cachedSerial !== '' && $liveSerial !== $cachedSerial;
                        });
                    $gponOnusByPon = $capabilityRecords
                        ->groupBy(fn (array $record): int => (int) $record['pon_port']);

                    foreach ($gponOnusByPon as $ponPort => $ponRecords) {
                        $client->command($this->ponInterfaceCommand($oltDevice, (int) $ponPort));

                        foreach ($ponRecords->pluck('onu_id')->map(fn ($onuId): int => (int) $onuId)->unique() as $onuId) {
                            $outputs[] = $this->optionalOltCommand($client, 'show ont-capability '.$onuId);
                        }

                        $client->command('exit');
                    }

                    $this->updateRefreshProgress(65, $capabilityRecords->count().' Ethernet port capabilities refreshed');
                }

                if (! $fastInventoryRefresh && $vlanCommand && $profile?->supports_vlan_polling && $this->isGlobalOnuPollingCommand($vlanCommand)) {
                    $outputs[] = $this->optionalOltCommand($client, $vlanCommand);
                }
                $this->updateRefreshProgress(72, 'VLAN data received');

                if ($this->shouldPollMacDetails($macCommand, $profile, $oltDevice, $fullDetailRefresh)) {
                    $outputs[] = $this->optionalOltCommand($client, $this->macPollingCommand($macCommand, $oltDevice, $selectedPonPort));
                }
                $this->updateRefreshProgress(88, 'Learned MAC data received');
            } else {
            if (! $fastInventoryRefresh && $vlanCommand && $profile?->supports_vlan_polling && $this->isGlobalOnuPollingCommand($vlanCommand)) {
                $outputs[] = $this->optionalOltCommand($client, $vlanCommand);
            }

            if ($this->shouldPollMacDetails($macCommand, $profile, $oltDevice, $fullDetailRefresh)) {
                $outputs[] = $this->optionalOltCommand($client, $this->macPollingCommand($macCommand, $oltDevice, $selectedPonPort));
            }

            foreach ($ponPorts as $ponIndex => $ponPort) {
                $client->command($this->ponInterfaceCommand($oltDevice, $ponPort));
                $statusOutput = $client->command($statusCommand);
                $statusRecords = $parser->parse($statusOutput);
                $authoritativeKeysByPon[$ponPort] = $this->onuRecordKeys($statusRecords, $ponPort);
                $outputs[] = $statusOutput;

                if (! $statusOnlyRefresh) {
                    $outputs[] = $client->command($powerCommand);
                }

                if (! $fastInventoryRefresh && ! $eponPowerVlanRefresh) {
                    foreach ($this->alarmCommandsForPort($alarmCommand, $statusRecords, $ponPort) as $pollAlarmCommand) {
                        $outputs[] = $client->command($pollAlarmCommand);
                    }
                }

                if (! $fastInventoryRefresh && $vlanCommand && $profile?->supports_vlan_polling && ! $this->isGlobalOnuPollingCommand($vlanCommand)) {
                    $vlanOnuIds = $this->onuIdsForVlanRefresh($statusRecords, $oltDevice, $ponPort, $eponPowerVlanRefresh);

                    foreach ($vlanOnuIds as $vlanIndex => $onuId) {
                        $client->command($this->onuContextCommand($oltDevice, $ponPort, $onuId));
                        $outputs[] = $this->optionalOltCommand($client, $vlanCommand);
                        $client->command('exit');

                        if ($eponPowerVlanRefresh && (($vlanIndex + 1) % 5 === 0 || $vlanIndex + 1 === count($vlanOnuIds))) {
                            $fractionalPon = $ponIndex + (($vlanIndex + 1) / max(1, count($vlanOnuIds)));
                            $vlanProgress = 25 + (int) round(($fractionalPon / max(1, count($ponPorts))) * 60);
                            $this->updateRefreshProgress(
                                $vlanProgress,
                                'PON '.$ponPort.' VLAN '.($vlanIndex + 1).'/'.count($vlanOnuIds)
                            );
                        }
                    }
                }

                // Return to config mode before selecting the next PON. HSGQ EPON
                // rejects "interface epon N" while still inside another PON.
                $client->command('exit');

                $ponProgress = 25 + (int) round((($ponIndex + 1) / max(1, count($ponPorts))) * 60);
                $this->updateRefreshProgress($ponProgress, 'PON '.$ponPort.' completed ('.($ponIndex + 1).'/'.count($ponPorts).')');

                // A complete HSGQ EPON VLAN scan can exceed the appliance's
                // interactive-session lifetime. Reconnect between PONs so a
                // dropped long-running SSH session cannot truncate the scan.
                if ($eponPowerVlanRefresh && $ponIndex + 1 < count($ponPorts)) {
                    $client->close();
                    $connectClient();

                    foreach ($this->baseContextCommands($contextCommands, $oltDevice) as $contextCommand) {
                        $client->command($contextCommand);
                    }
                }
            }
            }

            $client->close();

            $output = implode("\n", $outputs);
            $parsedRecords = $parser->parse($output);

            if ($selectedPonPort !== null) {
                $parsedRecords = array_values(array_filter(
                    $parsedRecords,
                    fn (array $record): bool => (int) ($record['pon_port'] ?? 0) === $selectedPonPort
                ));
                $authoritativeKeysByPon = isset($authoritativeKeysByPon[$selectedPonPort])
                    ? [$selectedPonPort => $authoritativeKeysByPon[$selectedPonPort]]
                    : [];
            }

            $records = $this->filterAuthoritativeLiveRecords($parsedRecords, $authoritativeKeysByPon);
            $this->updateRefreshProgress(92, 'Saving '.count($records).' ONU/ONT rows');
            $deleted = $this->deleteStaleLiveOnus($oltDevice, $authoritativeKeysByPon);
            $polledAt = now();

            foreach ($records as $record) {
                $this->updateLiveOnuRecord($oltDevice, $record, $polledAt);
            }

            $oltDevice->update([
                'last_polled_at' => $polledAt,
                'last_error' => null,
                'last_raw_output' => $output,
            ]);

            $message = count($records).' live ONU record(s) refreshed from '.$oltDevice->name.$this->refreshScopeText($selectedPonPort).' in '.$this->formatDurationSeconds($startedAt).' using '.$this->refreshModeText($fullDetailRefresh).'. '.$deleted.' stale/deleted ONU row(s) removed.';
            $this->updateRefreshProgress(100, $message, 'completed', true);

            return redirect()
                ->route($redirectRoute, array_merge(['olt_device_id' => $oltDevice->id], $redirectParams))
                ->with('success', $message);
        } catch (Throwable $exception) {
            $client->close();

            $error = Utf8Text::clean($exception->getMessage()) ?? 'Unknown error';

            $oltDevice->update([
                'last_error' => $error,
            ]);

            $this->failRefreshProgress('OLT live refresh failed: '.$error);

            return redirect()->route($redirectRoute, $redirectParams ?: ['olt_device_id' => $oltDevice->id])->with('error', 'OLT live refresh failed after '.$this->formatDurationSeconds($startedAt).': '.$error);
        }
    }

    public function refreshRunStatus(OltRefreshRun $oltRefreshRun)
    {
        return response()->json([
            'id' => $oltRefreshRun->id,
            'olt_device_id' => $oltRefreshRun->olt_device_id,
            'status' => $oltRefreshRun->status,
            'progress' => $oltRefreshRun->progress,
            'message' => $oltRefreshRun->message,
            'started_at' => $oltRefreshRun->started_at?->format('d/m/Y H:i:s'),
            'completed_at' => $oltRefreshRun->completed_at?->format('d/m/Y H:i:s'),
        ]);
    }

    private function startBackgroundRefresh(Request $request, OltDevice $oltDevice)
    {
        if ($oltDevice->status !== 'active') {
            $message = 'OLT is inactive. Activate it before live refresh.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('warning', $message);
        }

        $activeRun = $oltDevice->refreshRuns()
            ->whereIn('status', ['queued', 'running'])
            ->latest('id')
            ->first();

        if ($activeRun) {
            $payload = $this->refreshRunPayload($activeRun, 'A background refresh is already running.');

            return $request->expectsJson()
                ? response()->json($payload, 202)
                : redirect()->route('olt-onus.index', ['olt_device_id' => $oltDevice->id])
                    ->with('warning', $payload['message']);
        }

        $selectedPonPort = $this->selectedPonPort($this->refreshPonOptions($oltDevice));
        $run = OltRefreshRun::query()->create([
            'olt_device_id' => $oltDevice->id,
            'olt_name' => $oltDevice->name,
            'refresh_mode' => (string) $request->input('refresh_mode', 'full_mac'),
            'pon_port' => $selectedPonPort,
            'status' => 'queued',
            'progress' => 0,
            'message' => 'Queued; preparing background refresh',
        ]);

        // Laravel's process-backed "background" queue appends a POSIX `&`,
        // which does not start reliably under Windows/XAMPP. The deferred
        // queue sends the JSON response first, then performs the refresh in
        // the same Apache worker while progress remains pollable.
        RunOltFullRefresh::dispatch($run->id)->onConnection('deferred');

        $payload = $this->refreshRunPayload($run, 'Background Power/VLAN + MAC refresh started. You can keep using this page.');

        return $request->expectsJson()
            ? response()->json($payload, 202)
            : redirect()->route('olt-onus.index', ['olt_device_id' => $oltDevice->id])
                ->with('success', $payload['message']);
    }

    private function refreshRunPayload(OltRefreshRun $run, string $message): array
    {
        return [
            'message' => $message,
            'run' => [
                'id' => $run->id,
                'status' => $run->status,
                'progress' => $run->progress,
                'message' => $run->message,
                'status_url' => route('olt-onus.refresh-runs.show', $run),
            ],
        ];
    }

    private function updateRefreshProgress(int $progress, string $message, ?string $status = null, bool $completed = false): void
    {
        $runId = (int) request()->input('refresh_run_id');

        if ($runId < 1) {
            return;
        }

        $values = [
            'progress' => max(0, min(100, $progress)),
            'message' => $message,
        ];

        if ($status !== null) {
            $values['status'] = $status;
        }

        if ($status === 'running') {
            $values['started_at'] = now();
        }

        if ($completed) {
            $values['completed_at'] = now();
        }

        OltRefreshRun::query()->whereKey($runId)->update($values);
    }

    private function failRefreshProgress(string $message): void
    {
        $this->updateRefreshProgress(100, $message, 'failed', true);
    }

    public function refreshOnu(Request $request, OltOnu $oltOnu, OltSshClient $sshClient, OltTelnetClient $telnetClient, OltLiveOutputParser $parser)
    {
        // A row-level "Update Now" is expected to refresh every visible live
        // field, not just the fast status/power snapshot.
        if (! $request->filled('refresh_mode')) {
            $request->merge(['refresh_mode' => 'full_mac']);
        }

        try {
            $refreshedOnu = $this->performSingleOnuRefresh($oltOnu, $parser);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ONU '.$refreshedOnu->pon_port.'/'.$refreshedOnu->onu_id.' power, VLAN and learned MAC refreshed.',
                    'onu' => $this->onuPayload($refreshedOnu),
                ]);
            }

            return redirect()->route('olt-onus.show', [
                'oltOnu' => $refreshedOnu,
                'skip_auto_refresh' => 1,
            ])->with('success', 'ONU '.$refreshedOnu->pon_port.'/'.$refreshedOnu->onu_id.' refreshed and saved.');
        } catch (Throwable $exception) {
            $error = Utf8Text::clean($exception->getMessage()) ?? 'Unknown error';

            $oltOnu->oltDevice?->update([
                'last_error' => $error,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OLT live refresh failed: '.$error,
                ], 422);
            }

            return redirect()->route('olt-onus.show', $oltOnu)->with('error', 'OLT live refresh failed: '.$error);
        }
    }

    public function show(Request $request, OltOnu $oltOnu, OltLiveOutputParser $parser)
    {
        if (! $request->boolean('skip_auto_refresh')) {
            try {
                $oltOnu = $this->performSingleOnuRefresh($oltOnu, $parser);
            } catch (Throwable $exception) {
                session()->flash('error', 'OLT live refresh failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error'));
                $oltOnu = $oltOnu->fresh();
            }
        }

        return view('olt_onus.show', [
            'oltOnu' => $oltOnu->load('oltDevice'),
            'oltDevice' => $oltOnu->oltDevice,
        ]);
    }

    public function updateNote(Request $request, OltOnu $oltOnu)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:60000'],
        ]);

        $oltOnu->update(['note' => $data['note'] ?? null]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'onu' => $this->onuPayload($oltOnu->fresh()),
            ]);
        }

        return back()->with('success', 'ONU note saved.');
    }

    public function appendCurrentLaserToNote(Request $request, OltOnu $oltOnu)
    {
        $timestamp = now();
        $oltOnu->update([
            'note' => $this->appendLaserHistoryLine($oltOnu->note, $oltOnu->rx_power_dbm, $timestamp),
        ]);

        $message = "Current laser saved to note for {$oltOnu->pon_port}/{$oltOnu->onu_id}.";

        return $request->expectsJson()
            ? response()->json([
                'status' => 'success',
                'message' => $message,
                'onu' => $this->onuPayload($oltOnu->fresh()),
            ])
            : back()->with('success', $message);
    }

    public function appendCurrentLaserToAllNotes()
    {
        $timestamp = now();
        $updated = 0;

        OltOnu::query()
            ->select(['id', 'note', 'rx_power_dbm'])
            ->chunkById(200, function ($onus) use ($timestamp, &$updated): void {
                foreach ($onus as $onu) {
                    $onu->update([
                        'note' => $this->appendLaserHistoryLine($onu->note, $onu->rx_power_dbm, $timestamp),
                    ]);
                    $updated++;
                }
            });

        return back()->with('success', "Current laser saved to notes for {$updated} ONU/ONT row(s).");
    }

    private function appendLaserHistoryLine(?string $note, mixed $rxPowerDbm, $timestamp): string
    {
        $laser = $rxPowerDbm === null
            ? 'N/A (no live power)'
            : number_format((float) $rxPowerDbm, 2).' dBm';
        $line = $timestamp->format('d/m/Y H:i:s').' | Laser: '.$laser;

        return trim((string) $note) === '' ? $line : rtrim((string) $note)."\n".$line;
    }

    private function performSingleOnuRefresh(OltOnu $oltOnu, OltLiveOutputParser $parser): OltOnu
    {
        if (! $oltOnu->oltDevice) {
            throw new \RuntimeException('This ONU is not linked to an OLT device.');
        }

        $oltDevice = $oltOnu->oltDevice;

        if ($oltDevice->status !== 'active') {
            throw new \RuntimeException('OLT is inactive. Activate it before live refresh.');
        }

        $contextCommands = $this->contextCommands($oltDevice->read_context_commands);
        $blockedContextCommand = $this->firstUnsafeContextCommand($contextCommands);

        if ($blockedContextCommand) {
            throw new \RuntimeException('Unsafe OLT context command blocked. Only enable/config/interface epon/interface gpon/exit navigation is allowed: '.$blockedContextCommand);
        }

        $profile = $this->protocolProfile($oltDevice);
        $statusCommand = $this->normalizeOltStatusCommand($oltDevice->onu_status_command ?: $profile?->default_onu_status_command);
        $powerCommand = $oltDevice->onu_power_command ?: $profile?->default_onu_power_command;
        $vlanCommand = $oltDevice->onu_vlan_command ?: $profile?->default_onu_vlan_command;
        $macCommand = $oltDevice->onu_mac_command ?: $profile?->default_onu_mac_command;
        $fullDetailRefresh = in_array(request()->input('refresh_mode'), ['full', 'full_mac'], true);
        if (! $fullDetailRefresh && $snmpRecord = app(OltSnmpClient::class)->singleOnuSnapshot($oltDevice, $oltOnu)) {
            $this->updateLiveOnuRecord($oltDevice, $snmpRecord, now());

            return OltOnu::query()->with('oltDevice')->findOrFail($oltOnu->id);
        }

        $commands = $this->singleOnuPollCommands($oltDevice, $oltOnu, $statusCommand, $powerCommand, $vlanCommand, $macCommand, $profile, $fullDetailRefresh);
        $blockedCommand = $this->firstUnsafeReadOrContextCommand($commands);

        if ($blockedCommand) {
            throw new \RuntimeException('Unsafe OLT command blocked. Only read-only show/display/interface commands are allowed: '.$blockedCommand);
        }

        $output = $this->runOltReadCommands($oltDevice, $commands);
        $record = null;

        foreach ($parser->parse($output) as $candidate) {
            if ((int) ($candidate['pon_port'] ?? 0) === $oltOnu->pon_port && (int) ($candidate['onu_id'] ?? -1) === $oltOnu->onu_id) {
                $record = $candidate;
                break;
            }
        }

        if (! $record) {
            throw new \RuntimeException('Live refresh completed, but the current ONU was not found in OLT output.');
        }

        $record['raw_live_output'] = $output;
        $this->updateLiveOnuRecord($oltDevice, $record, now());

        return OltOnu::query()->with('oltDevice')->findOrFail($oltOnu->id);
    }

    private function onuPayload(OltOnu $oltOnu): array
    {
        $oltOnu->loadMissing('oltDevice');

        return [
            'id' => $oltOnu->id,
            'pon_onu' => $oltOnu->pon_port.'/'.$oltOnu->onu_id,
            'olt' => $oltOnu->oltDevice?->name ?? $oltOnu->olt_name ?? 'N/A',
            'mac_address' => $oltOnu->mac_address ?: 'N/A',
            'status' => $oltOnu->status ?: 'unknown',
            'status_badge_class' => in_array($oltOnu->status, ['online', 'active'], true) ? 'active' : ($oltOnu->status ? 'pending' : 'inactive'),
            'rx_power_dbm' => $oltOnu->rx_power_dbm !== null ? number_format((float) $oltOnu->rx_power_dbm, 2).' dBm' : 'No live power',
            'tx_power_dbm' => $oltOnu->tx_power_dbm !== null ? number_format((float) $oltOnu->tx_power_dbm, 2).' dBm' : null,
            'power_badge_class' => $oltOnu->rx_power_dbm !== null ? ((float) $oltOnu->rx_power_dbm <= -25 ? 'failed' : 'active') : '',
            'power_html' => $this->onuPowerHtml($oltOnu),
            'vlans_html' => $this->vlanBadgesHtml($oltOnu->port_vlans ?: []),
            'learned_macs_html' => $this->learnedMacsHtml($oltOnu->learned_macs ?: [], $oltOnu->port_vlans ?: []),
            'last_live_polled_at' => $oltOnu->last_live_polled_at?->format('d/m/Y H:i:s') ?? 'Never',
            'note' => $oltOnu->note ?? '',
        ];
    }

    /** Rx + Tx optical-power badges as one HTML string, shared by the list and detail refresh. */
    private function onuPowerHtml(OltOnu $oltOnu): string
    {
        $rx = $oltOnu->rx_power_dbm;
        $tx = $oltOnu->tx_power_dbm;

        if ($rx === null && $tx === null) {
            return '<span class="muted">No live power</span>';
        }

        $parts = [];

        if ($rx !== null) {
            $rxClass = (float) $rx <= -25 ? 'failed' : 'active';
            $parts[] = '<span class="badge '.$rxClass.'">Rx '.number_format((float) $rx, 2).' dBm</span>';
        }

        if ($tx !== null) {
            $txClass = ((float) $tx <= 0.5 || (float) $tx >= 7) ? 'failed' : 'active';
            $parts[] = '<span class="badge '.$txClass.'">Tx '.number_format((float) $tx, 2).' dBm</span>';
        }

        return implode(' ', $parts);
    }

    private function vlanBadgesHtml(array $portVlans): string
    {
        if ($portVlans === []) {
            return '<span class="muted">No VLAN config</span>';
        }

        return collect($portVlans)->map(function (array $vlan): string {
            $port = e((string) ($vlan['port'] ?? '?'));
            $value = e((string) (array_key_exists('vlan', $vlan) && $vlan['vlan'] !== null ? $vlan['vlan'] : ($vlan['mode'] ?? '?')));

            return '<span class="badge">'.$port.': '.$value.'</span>';
        })->implode(' ');
    }

    private function learnedMacsHtml(array $learnedMacs, array $portVlans = []): string
    {
        $learnedMacs = $this->expandLearnedMacVlans($learnedMacs, $portVlans);

        if ($learnedMacs === []) {
            return '<span class="muted">No learned MAC</span>';
        }

        return collect($learnedMacs)->map(function (array $learnedMac): string {
            $mac = e((string) ($learnedMac['mac'] ?? '?'));
            $vlan = isset($learnedMac['vlan'])
                ? ' <span class="muted">VLAN '.e((string) $learnedMac['vlan']).'</span>'
                : '';

            return '<div><span class="badge">'.$mac.'</span>'.$vlan.'</div>';
        })->implode('');
    }

    private function expandLearnedMacVlans(array $learnedMacs, array $portVlans): array
    {
        if (count($learnedMacs) !== 1 || $portVlans === []) {
            return $learnedMacs;
        }

        $base = $learnedMacs[0];
        $mac = $base['mac'] ?? null;

        if (! $mac) {
            return $learnedMacs;
        }

        $vlans = collect($portVlans)
            ->pluck('vlan')
            ->filter(fn ($vlan) => $vlan !== null && $vlan !== '')
            ->unique()
            ->values();

        if ($vlans->count() <= 1) {
            return $learnedMacs;
        }

        return $vlans
            ->map(function ($vlan) use ($base): array {
                $entry = $base;
                $entry['vlan'] = (int) $vlan;

                return $entry;
            })
            ->all();
    }

    private function singleOnuPollCommands(
        OltDevice $oltDevice,
        OltOnu $oltOnu,
        ?string $statusCommand,
        ?string $powerCommand = null,
        ?string $vlanCommand = null,
        ?string $macCommand = null,
        ?OltProtocolProfile $profile = null,
        bool $fullDetailRefresh = false
    ): array
    {
        $ponInterfaceTemplate = $profile?->pon_interface_command ?: 'interface epon {pon_port}';

        $commands = [
            str_replace('{pon_port}', (string) $oltOnu->pon_port, $ponInterfaceTemplate),
        ];

        $command = trim((string) $this->normalizeOltStatusCommand($statusCommand));

        if ($command === '') {
            $command = $oltDevice->protocol_profile === 'hsgq_gpon' 
                ? 'show ont-info all' 
                : 'show onu-info all';
        }

        if ($this->usesHsgqEpon($oltDevice) && preg_match('/\bshow\s+onu-info\s+all\b/i', $command)) {
            $command = 'show onu-info all';
        } elseif (preg_match('/\ball\b/i', $command)) {
            $command = preg_replace('/\ball\b/i', (string) $oltOnu->onu_id, $command);
        } elseif (str_contains($command, '{onu_id}')) {
            $command = $this->substituteOnuPollTemplate($command, $oltOnu);
        }

        $commands[] = $command;

        if ($powerCommand) {
            $commands[] = $this->substituteOnuPollTemplate($powerCommand, $oltOnu);
        }

        if ($fullDetailRefresh && $oltDevice->protocol_profile === 'hsgq_gpon') {
            $commands[] = 'show ont-capability '.$oltOnu->onu_id;
        }

        $commands[] = 'exit';

        if ($fullDetailRefresh && $vlanCommand && $profile?->supports_vlan_polling && ! $this->isGlobalOnuPollingCommand($vlanCommand)) {
            $commands[] = $this->onuContextCommand($oltDevice, $oltOnu->pon_port, $oltOnu->onu_id);
            $commands[] = $this->substituteOnuPollTemplate($vlanCommand, $oltOnu);
            $commands[] = 'exit';
        } elseif ($fullDetailRefresh && $vlanCommand && $profile?->supports_vlan_polling && $this->isGlobalOnuPollingCommand($vlanCommand)) {
            $commands[] = $this->substituteOnuPollTemplate($vlanCommand, $oltOnu);
        }

        if ($fullDetailRefresh && $macCommand && $profile?->supports_mac_polling) {
            $commands[] = $this->macPollingCommand($macCommand, $oltDevice, $oltOnu->pon_port);
        }

        return array_values(array_filter($commands));
    }

    private function normalizeOltStatusCommand(?string $command): ?string
    {
        if ($command === null) {
            return null;
        }

        $normalized = trim((string) $command);

        if ($normalized === '') {
            return $normalized;
        }

        return preg_replace('/\b(onu|ont)-infoall\b/i', '$1-info all', $normalized) ?? $normalized;
    }

    private function substituteOnuPollTemplate(string $command, OltOnu $oltOnu): string
    {
        return str_replace(
            ['{pon_port}', '{onu_id}'],
            [(string) $oltOnu->pon_port, (string) $oltOnu->onu_id],
            $command
        );
    }

    public function refreshForAutoDiscovery(Request $request, OltDevice $oltDevice, OltSshClient $sshClient, OltTelnetClient $telnetClient, OltLiveOutputParser $parser)
    {
        return $this->refresh($request, $oltDevice, $sshClient, $telnetClient, $parser, 'olt-onus.auto-discovery', ['olt_device_id' => $oltDevice->id]);
    }

    private function contextCommands(?string $commands): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/', (string) $commands) ?: []),
            fn (string $command): bool => $command !== ''
        ));
    }

    private function onuUtilityList(Request $request, string $type)
    {
        $oltDevices = OltDevice::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $selectedOltId = $request->filled('olt_device_id') ? $request->integer('olt_device_id') : null;
        $selectedOlt = $selectedOltId ? $oltDevices->firstWhere('id', $selectedOltId) : null;
        $targetOlts = $selectedOlt ? collect([$selectedOlt]) : $oltDevices;
        $outputs = [];
        $rows = [];

        foreach ($targetOlts as $oltDevice) {
            foreach ($this->utilityListCommandGroups($oltDevice, $type) as $commandGroup) {
                $commands = $commandGroup['commands'];
                $label = $commandGroup['label'];

                try {
                    $output = $this->runOltUtilityCommands($oltDevice, $commands);
                } catch (Throwable $exception) {
                    $output = 'Command failed: '.implode(' -> ', $commands)."\n".(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error');
                }

                $outputs[$oltDevice->name.' - '.$label] = $output;
                $rows = array_merge($rows, $this->parseUtilityRows($output, $oltDevice, $type));
            }
        }

        return view('olt_onus.utility_list', [
            'type' => $type,
            'title' => $type === 'deny' ? 'ONU Deny List' : 'Auto Discovery List',
            'oltDevices' => $oltDevices,
            'selectedOlt' => $selectedOlt,
            'outputs' => $outputs,
            'rows' => $rows,
            'nextOnuId' => $selectedOlt ? $this->nextOnuId($selectedOlt, 1) : 1,
            'showingAllOlts' => $selectedOlt === null,
        ]);
    }

    private function utilityListCommands(OltDevice $oltDevice, string $type): array
    {
        return array_map(
            fn (array $group): string => $group['label'],
            $this->utilityListCommandGroups($oltDevice, $type)
        );
    }

    private function utilityListCommandGroups(OltDevice $oltDevice, string $type): array
    {
        if ($type === 'deny') {
            if ($this->usesHsgqEpon($oltDevice)) {
                $ports = $this->ponPorts($oltDevice->pon_ports) ?: range(1, 8);
                $commands = ['config'];

                foreach ($ports as $ponPort) {
                    $commands[] = "interface epon {$ponPort}";
                    $commands[] = 'show blacklist onu-info all';
                    $commands[] = 'exit';
                }

                return [[
                    'label' => 'All configured PON deny candidates',
                    'commands' => $commands,
                ]];
            }

            return $oltDevice->protocol_profile === 'hsgq_gpon'
                ? [['label' => 'show black-ont all', 'commands' => ['show black-ont all']]]
                : [['label' => 'show black-onu all', 'commands' => ['show black-onu all']]];
        }

        if ($oltDevice->protocol_profile === 'hsgq_gpon') {
            $ports = $this->ponPorts($oltDevice->pon_ports) ?: range(1, 8);
            $commands = ['config'];

            foreach ($ports as $ponPort) {
                $commands[] = "interface gpon {$ponPort}";
                $commands[] = 'show ont-autofind';
                $commands[] = 'exit';
            }

            return [[
                'label' => 'All configured PON auto discovery candidates',
                'commands' => $commands,
            ]];
        }

        return [['label' => 'show onu-autofind all', 'commands' => ['show onu-autofind all']]];
    }

    private function parseUtilityRows(string $output, OltDevice $oltDevice, string $type): array
    {
        $rows = [];
        $detailPonPort = null;
        $detailOnuId = null;

        foreach (preg_split('/\R/', Utf8Text::clean($output) ?? '') ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || preg_match('/^(show|---|total|warning|there|command failed)/i', $line)) {
                continue;
            }

            if ($type === 'discovery' && $oltDevice->protocol_profile === 'hsgq_gpon') {
                if (preg_match('/^interface\s+gpon\s+(\d{1,2})$/i', $line, $match)) {
                    $detailPonPort = (int) $match[1];
                    $detailOnuId = null;

                    continue;
                }

                if (preg_match('/^PON\s+ID\s*:\s*(\d{1,2})$/i', $line, $match)) {
                    $detailPonPort = (int) $match[1];

                    continue;
                }

                if (preg_match('/^(?:ONU|ONT)\s+ID\s*:\s*(\d{1,3})$/i', $line, $match)) {
                    $detailOnuId = (int) $match[1];

                    continue;
                }

                if (
                    $detailPonPort !== null
                    && $detailOnuId !== null
                    && preg_match('/^(?:ONU|ONT)\s+SN\s*:\s*(.+)$/i', $line, $match)
                ) {
                    $serialValue = trim($match[1]);
                    $serial = null;

                    if (preg_match('/\(([^)]+)\)/', $serialValue, $serialMatch)) {
                        $serial = $this->parseUtilitySerial($serialMatch[1], '');
                    }

                    $serial ??= $this->parseUtilitySerial($serialValue, '');

                    if ($serial !== null) {
                        $rows[] = [
                            'olt_device_id' => $oltDevice->id,
                            'olt_name' => $oltDevice->name,
                            'olt_protocol_profile' => $oltDevice->protocol_profile,
                            'pon_port' => $detailPonPort,
                            'onu_id' => $this->nextOnuId($oltDevice, $detailPonPort),
                            'source_onu_id' => $detailOnuId,
                            'auto_assign_onu_id' => false,
                            'serial' => $serial,
                            'status' => 'discovered',
                            'raw' => 'PON ID: '.$detailPonPort.' | ONU ID: '.$detailOnuId.' | '.$line,
                        ];
                    }

                    continue;
                }
            }

            if (preg_match('/^\S+(?:\([^)]+\))?[#>]/', $line)) {
                continue;
            }

            if (preg_match('/^(enable|config|configure|exit|interface\s+(?:epon|gpon)\s+\d{1,2})$/i', $line)) {
                continue;
            }

            if (preg_match('/\b(?:unknown command|command incomplete|invalid input|vty%|\[VTY\]|node:\d+|config[#\)]|enable[#>]|\S+[#>])\b/i', $line)) {
                continue;
            }

            $serial = $this->parseUtilitySerial($line, '');

            if ($serial === null) {
                continue;
            }

            if (
                $type === 'deny'
                && $this->usesHsgqEpon($oltDevice)
                && $this->isEponOnuInfoRow($line)
                && ! $this->isEponDenyCandidate($line)
            ) {
                continue;
            }

            $ponOnu = $this->parseUtilityPonOnu($line, $serial);

            if ($ponOnu === null) {
                continue;
            }

            [$ponPort, $sourceOnuId] = $ponOnu;
            $autoAssignOnuId = $type === 'deny' && $this->usesHsgqEpon($oltDevice);

            $rows[] = [
                'olt_device_id' => $oltDevice->id,
                'olt_name' => $oltDevice->name,
                'olt_protocol_profile' => $oltDevice->protocol_profile,
                'pon_port' => $ponPort,
                'onu_id' => $autoAssignOnuId
                    ? 0
                    : ($type === 'discovery' ? $this->nextOnuId($oltDevice, $ponPort) : $sourceOnuId),
                'source_onu_id' => $sourceOnuId,
                'auto_assign_onu_id' => $autoAssignOnuId,
                'serial' => $serial,
                'status' => $type === 'deny' ? 'deny' : 'discovered',
                'raw' => $line,
            ];
        }

        return $rows;
    }

    private function isEponOnuInfoRow(string $line): bool
    {
        return preg_match('/^\d{1,2}\s*\/\s*\d{1,3}\s+[0-9a-f]{2}(?::[0-9a-f]{2}){5}\s+\S+\s+(?:TRUE|FALSE)\s+(?:TRUE|FALSE)\b/i', $line) === 1;
    }

    private function isEponDenyCandidate(string $line): bool
    {
        if (! preg_match('/^\d{1,2}\s*\/\s*\d{1,3}\s+[0-9a-f]{2}(?::[0-9a-f]{2}){5}\s+(\S+)\s+(?:TRUE|FALSE)\s+FALSE\b/i', $line, $match)) {
            return false;
        }

        return ! in_array(strtolower($match[1]), ['initial', 'init'], true);
    }

    private function parseUtilityPonOnu(string $line, string $serial): ?array
    {
        if (preg_match('/\b(?:epon|gpon|pon)\s*0?(\d{1,2})\s+(\d{1,3})\b/i', $line, $match)) {
            return [(int) $match[1], (int) $match[2]];
        }

        if (preg_match('/\b(\d{1,2})\s*\/\s*(\d{1,3})\b/', $line, $match)) {
            return [(int) $match[1], (int) $match[2]];
        }

        if (preg_match('/\b(\d{1,2})\s*:\s*(\d{1,3})\b/', $line, $match)) {
            return [(int) $match[1], (int) $match[2]];
        }

        $beforeSerial = trim(strstr($line, $serial, true) ?: $line);
        preg_match_all('/\b\d{1,3}\b/', $beforeSerial, $matches);
        $numbers = array_map('intval', $matches[0] ?? []);

        if ($numbers === []) {
            return null;
        }

        $ponPort = $numbers[0];
        $onuId = $numbers[1] ?? null;

        if ($ponPort < 1 || $ponPort > 16 || ($onuId !== null && ($onuId < 0 || $onuId > 256))) {
            return null;
        }

        return [$ponPort, $onuId];
    }

    private function nextOnuId(OltDevice $oltDevice, int $ponPort): int
    {
        $usedOnuIds = OltOnu::query()
            ->where('olt_device_id', $oltDevice->id)
            ->where('pon_port', $ponPort)
            ->whereNull('last_deregistered_at')
            ->where('status', '!=', 'deregistered')
            ->orderBy('onu_id')
            ->pluck('onu_id')
            ->all();

        $startId = $this->usesHsgqEpon($oltDevice) ? 0 : 1;

        for ($onuId = $startId; $onuId <= 256; $onuId++) {
            if (! in_array($onuId, $usedOnuIds, true)) {
                return $onuId;
            }
        }

        return ((int) end($usedOnuIds)) + 1;
    }

    private function resolveOnuId(OltDevice $oltDevice, int $ponPort, ?int $requestedOnuId = null, ?array $existingOnus = null): int
    {
        if ($requestedOnuId === 0 && $oltDevice->protocol_profile === 'hsgq_gpon') {
            $requestedOnuId = null;
        }

        if ($existingOnus !== null) {
            $usedOnuIds = array_values(array_unique(array_map(function (array $onuRecord) use ($ponPort): int {
                return $onuRecord['pon_port'] === $ponPort ? (int) $onuRecord['onu_id'] : -1;
            }, $existingOnus)));

            $usedOnuIds = array_values(array_filter($usedOnuIds, fn (int $id): bool => $id >= 0));

            if ($requestedOnuId !== null && ! in_array($requestedOnuId, $usedOnuIds, true)) {
                return $requestedOnuId;
            }

            $startId = $this->usesHsgqEpon($oltDevice) ? 0 : 1;

            for ($onuId = $startId; $onuId <= 256; $onuId++) {
                if (! in_array($onuId, $usedOnuIds, true)) {
                    return $onuId;
                }
            }

            return ((int) end($usedOnuIds)) + 1;
        }

        if ($requestedOnuId !== null) {
            $exists = OltOnu::query()
                ->where('olt_device_id', $oltDevice->id)
                ->where('pon_port', $ponPort)
                ->where('onu_id', $requestedOnuId)
                ->exists();

            if (! $exists) {
                return $requestedOnuId;
            }
        }

        return $this->nextOnuId($oltDevice, $ponPort);
    }

    private function fetchExistingGponOnus(OltDevice $oltDevice, OltLiveOutputParser $parser): array
    {
        $output = $this->runOltReadCommands($oltDevice, ['show ont-info all']);

        return $parser->parse($output);
    }

    private function findExistingGponOnuBySerial(array $records, string $serial): ?array
    {
        $serial = strtolower($serial);

        foreach ($records as $record) {
            if (isset($record['mac_address']) && strtolower($record['mac_address']) === $serial) {
                return $record;
            }
        }

        return null;
    }

    private function findEponOnuIdInOutput(string $output, OltDevice $oltDevice, string $serial): ?int
    {
        $serial = strtolower($serial);

        foreach (preg_split('/\R/', Utf8Text::clean($output) ?? '') ?: [] as $line) {
            $line = trim($line);

            if (stripos($line, $serial) === false) {
                continue;
            }

            if (preg_match('/\b(\d{1,2})\s*\/\s*(\d{1,3})\b/', $line, $match)) {
                return (int) $match[2];
            }
        }

        return null;
    }

    private function authorizeExistingGponOnuCommands(OltDevice $oltDevice, ?OltProtocolProfile $profile, array $data): array
    {
        $ponPort = (int) $data['pon_port'];
        $onuId = (int) $data['onu_id'];
        $vlan = (int) $data['vlan'];
        $ethernetPort = (int) $data['ethernet_port'];
        $name = $this->oltQuoted($data['name']);

        return array_values(array_filter([
            "interface gpon {$ponPort}",
            "?ont modify {$ponPort} {$onuId} desc {$name}",
            "ont port native-vlan {$onuId} eth {$ethernetPort} vlan {$vlan} 0",
            'exit',
            $profile?->save_config_command ?: 'save',
        ]));
    }

    private function verifyAndRepairGponOnuName(OltDevice $oltDevice, OltLiveOutputParser $parser, array $data): array
    {
        $ponPort = (int) $data['pon_port'];
        $onuId = (int) $data['onu_id'];
        $desiredName = trim((string) $data['name']);
        $output = [];

        $record = $this->queryGponOnuRecord($oltDevice, $parser, $ponPort, $onuId);
        $currentName = trim((string) ($record['name'] ?? ''));
        $output[] = 'Name verify before repair: '.($currentName !== '' ? $currentName : 'not found');

        if ($this->oltNamesMatch($currentName, $desiredName)) {
            return [
                'name' => $currentName,
                'matched' => true,
                'output' => implode("\n", $output),
            ];
        }

        try {
            $output[] = $this->runOltWriteCommands($oltDevice, $this->gponOnuNameRepairCommands($data));
        } catch (Throwable $exception) {
            $output[] = 'Name repair command failed: '.(Utf8Text::clean($exception->getMessage()) ?? 'Unknown error');
        }

        $record = $this->queryGponOnuRecord($oltDevice, $parser, $ponPort, $onuId);
        $currentName = trim((string) ($record['name'] ?? $currentName));
        $output[] = 'Name verify after repair: '.($currentName !== '' ? $currentName : 'not found');

        return [
            'name' => $currentName !== '' ? $currentName : null,
            'matched' => $this->oltNamesMatch($currentName, $desiredName),
            'output' => implode("\n", array_filter($output)),
        ];
    }

    private function queryGponOnuRecord(OltDevice $oltDevice, OltLiveOutputParser $parser, int $ponPort, int $onuId): ?array
    {
        try {
            $output = $this->runOltReadCommands($oltDevice, [
                "interface gpon {$ponPort}",
                'show ont-info all',
                "show ont-info {$onuId}",
            ]);
        } catch (Throwable) {
            return null;
        }

        foreach ($parser->parse($output) as $record) {
            if ((int) ($record['pon_port'] ?? 0) === $ponPort && (int) ($record['onu_id'] ?? -1) === $onuId) {
                return $record;
            }
        }

        return null;
    }

    private function gponOnuNameRepairCommands(array $data): array
    {
        $ponPort = (int) $data['pon_port'];
        $onuId = (int) $data['onu_id'];
        $name = $this->oltQuoted($data['name']);

        return [
            "interface gpon {$ponPort}",
            "?ont name {$onuId} {$name}",
            "?ont rename {$onuId} {$name}",
            "?ont modify {$onuId} name {$name}",
            "?ont modify {$onuId} desc {$name}",
            "?ont modify {$ponPort} {$onuId} name {$name}",
            "?ont modify {$ponPort} {$onuId} desc {$name}",
            "?ont description {$onuId} {$name}",
            "?ont set {$onuId} name {$name}",
            'exit',
        ];
    }

    private function eponOnuDescriptionCommands(array $data): array
    {
        $ponPort = (int) $data['pon_port'];
        $onuId = (int) $data['onu_id'];
        $name = $this->oltQuoted($data['name']);
        $macAddress = strtolower(trim((string) ($data['mac_address'] ?? '')));
        $onuType = trim((string) ($data['onu_type'] ?? '')) ?: '1ge';

        // Include many common vendor command variations and optional variants
        return array_values(array_filter([
            // enter PON context
            "interface epon {$ponPort}",

            // HSGQ EPON uses this syntax when authorizing ONU; on several firmwares
            // re-running it updates the ONU name for an existing binding.
            $macAddress !== '' ? "?bind-onu {$onuId} mac {$macAddress} onu-type {$onuType} name {$name}" : null,

            // common EPON/ONT variants
            "?onu modify {$ponPort} {$onuId} desc {$name}",
            "?onu modify {$ponPort} {$onuId} description {$name}",
            "?onu modify {$ponPort} {$onuId} name {$name}",
            "?onu set {$onuId} name {$name}",
            "?onu set {$onuId} description {$name}",
            "?onu rename {$onuId} {$name}",
            "?onu description {$onuId} {$name}",

            // GPON-like aliases that sometimes appear on EPON devices
            "?ont modify {$ponPort} {$onuId} desc {$name}",
            "?ont modify {$ponPort} {$onuId} name {$name}",
            "?ont name {$onuId} {$name}",
            "?ont rename {$onuId} {$name}",

            // Some devices accept direct interface ONU context
            "interface onu {$ponPort}/{$onuId}",
            "?name {$name}",
            "?description {$name}",

            // Try set and config variants prefixed with optional marker so failures don't abort
            "?set onu {$onuId} name {$name}",
            "?set onu {$onuId} description {$name}",

            // On HSGQ EPON, exiting ONU context returns to config mode.
            'exit',
        ]));
    }

    private function queryOnuRecordGeneric(OltDevice $oltDevice, OltLiveOutputParser $parser, int $ponPort, int $onuId): ?array
    {
        $profile = $this->protocolProfile($oltDevice);

        $readTemplate = $profile?->default_onu_status_command ?: $oltDevice->onu_status_command ?: 'show onu-info all';

        $possibleReads = [
            $readTemplate,
            str_replace('{onu_id}', (string) $onuId, $readTemplate),
            "show onu-info {$onuId}",
            "show ont-info {$onuId}",
            'show onu-info all',
        ];

        $commands = array_values(array_filter(array_unique(array_merge([
            $this->ponInterfaceCommand($oltDevice, $ponPort),
        ], $possibleReads))));

        try {
            $output = $this->runOltReadCommands($oltDevice, $commands);
        } catch (Throwable) {
            return null;
        }

        foreach ($parser->parse($output) as $record) {
            if ((int) ($record['pon_port'] ?? 0) === $ponPort && (int) ($record['onu_id'] ?? -1) === $onuId) {
                return $record;
            }
        }

        return null;
    }

    private function oltNamesMatch(?string $actual, string $expected): bool
    {
        $normalize = fn (?string $value): string => mb_strtolower($this->cleanOltReadbackName((string) $value));
        $actual = $normalize($actual);
        $expected = $normalize($expected);

        if ($actual === $expected) {
            return true;
        }

        if (mb_strlen($expected) >= 6 && str_contains($actual, $expected)) {
            return true;
        }

        return mb_strlen($actual) >= 8 && str_starts_with($expected, $actual);
    }

    private function oltNameReadbackWasTruncated(?string $actual, string $expected): bool
    {
        $actual = mb_strtolower($this->cleanOltReadbackName((string) $actual));
        $expected = mb_strtolower($this->cleanOltReadbackName($expected));

        return $actual !== ''
            && $actual !== $expected
            && mb_strlen($actual) < mb_strlen($expected)
            && str_starts_with($expected, $actual);
    }

    private function cleanOltReadbackName(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value, "\"' \t\n\r\0\x0B")) ?? '';
        $length = mb_strlen($value);

        if ($length > 0 && $length % 2 === 1) {
            $middle = intdiv($length, 2);

            if (mb_substr($value, $middle, 1) === ' ') {
                $left = mb_substr($value, 0, $middle);
                $right = mb_substr($value, $middle + 1);

                if (mb_strtolower($left) === mb_strtolower($right)) {
                    return $left;
                }
            }
        }

        return $value;
    }

    private function parseUtilitySerial(string $line, string $matchedKey): ?string
    {
        $remaining = trim(substr($line, strpos($line, $matchedKey) + strlen($matchedKey)));

        if ($remaining === '') {
            return null;
        }

        if (preg_match('/\b([0-9a-f]{2}(?::[0-9a-f]{2}){5}|[A-Z0-9][A-Z0-9:-]{5,})\b/i', $remaining, $serialMatch)) {
            return $serialMatch[1];
        }

        return null;
    }

    private function runOltUtilityCommands(OltDevice $oltDevice, array $commands): string
    {
        $commands = array_values(array_filter(array_map('trim', $commands)));
        $accessMethod = $this->utilityAccessMethod($oltDevice);
        $port = $accessMethod === 'telnet' && $oltDevice->access_method !== 'telnet'
            ? 23
            : (int) $oltDevice->port;

        try {
            $output = $this->runOltUtilityCommandsWithMethod($oltDevice, $commands, $accessMethod, $port);

            if ($this->shouldRetryUtilityOverTelnet($oltDevice, $output)) {
                $telnetOutput = $this->runOltUtilityCommandsWithMethod($oltDevice, $commands, 'telnet', 23);
                $output .= "\n\nRetried over telnet:\n".$telnetOutput;
            }

            $this->markOltConnectionSucceeded($oltDevice);

            return $output;
        } catch (Throwable $exception) {
            $this->markOltOperationFailed($oltDevice, $exception);

            throw $exception;
        }
    }

    private function utilityAccessMethod(OltDevice $oltDevice): string
    {
        return $oltDevice->access_method;
    }

    private function readAccessMethod(OltDevice $oltDevice): string
    {
        return $oltDevice->access_method;
    }

    private function usesFastEponRefresh(OltDevice $oltDevice): bool
    {
        return $this->usesHsgqEpon($oltDevice);
    }

    private function usesFastInventoryRefresh(OltDevice $oltDevice): bool
    {
        return in_array($oltDevice->protocol_profile, ['hsgq_epon', 'hsgq_gpon'], true);
    }

    private function usesStatusOnlyRefresh(OltDevice $oltDevice): bool
    {
        return $this->usesHsgqEpon($oltDevice);
    }

    private function usesEponPowerVlanRefresh(OltDevice $oltDevice, bool $fullDetailRefresh): bool
    {
        return $fullDetailRefresh && $this->usesHsgqEpon($oltDevice);
    }

    private function refreshPonOptions(OltDevice $oltDevice): array
    {
        return $this->ponPorts($oltDevice->pon_ports);
    }

    private function readTimeoutSeconds(OltDevice $oltDevice, bool $fullDetailRefresh): int
    {
        if ($this->usesEponPowerVlanRefresh($oltDevice, $fullDetailRefresh)) {
            return 20;
        }

        return 8;
    }

    private function selectedPonPort(array $ponPorts): ?int
    {
        $value = request()->input('pon_port');

        if ($value === null || $value === '') {
            return null;
        }

        $ponPort = (int) $value;

        return in_array($ponPort, $ponPorts, true) ? $ponPort : null;
    }

    private function refreshScopeText(?int $selectedPonPort): string
    {
        return $selectedPonPort === null ? '' : ' PON '.$selectedPonPort;
    }

    private function refreshModeText(bool $fullDetailRefresh): string
    {
        if (request()->input('refresh_mode') === 'full_mac') {
            return 'full Power/VLAN and MAC refresh mode';
        }

        if (request()->input('refresh_mode') === 'mac') {
            return 'MAC refresh mode';
        }

        return $fullDetailRefresh ? 'full Power/VLAN mode' : 'fast status mode';
    }

    private function shouldPollMacDetails(?string $macCommand, ?OltProtocolProfile $profile, OltDevice $oltDevice, bool $fullDetailRefresh): bool
    {
        if (! $macCommand || ! $profile?->supports_mac_polling) {
            return false;
        }

        if (in_array(request()->input('refresh_mode'), ['mac', 'full_mac'], true)) {
            return true;
        }

        return $this->usesEponPowerVlanRefresh($oltDevice, $fullDetailRefresh);
    }

    private function macPollingCommand(string $macCommand, OltDevice $oltDevice, ?int $selectedPonPort): string
    {
        if ($selectedPonPort !== null && $oltDevice->protocol_profile === 'hsgq_gpon') {
            return 'show mac-address port gpon '.$selectedPonPort;
        }

        return $macCommand;
    }

    private function usesGlobalGponStatusRefresh(OltDevice $oltDevice, ?string $statusCommand): bool
    {
        return $oltDevice->protocol_profile === 'hsgq_gpon'
            && $this->isGlobalOnuPollingCommand((string) $statusCommand);
    }

    private function onuRecordKeysGroupedByPon(array $records): array
    {
        $keys = [];

        foreach ($records as $record) {
            if (! isset($record['pon_port'], $record['onu_id'])) {
                continue;
            }

            $ponPort = (int) $record['pon_port'];
            $keys[$ponPort] ??= [];
            $keys[$ponPort][] = $ponPort.'/'.$record['onu_id'];
        }

        return array_map(
            fn (array $ponKeys): array => array_values(array_unique($ponKeys)),
            $keys
        );
    }

    private function formatDurationSeconds(float $startedAt): string
    {
        return number_format(max(0, microtime(true) - $startedAt), 2).' seconds';
    }

    private function runOltUtilityCommandsWithMethod(OltDevice $oltDevice, array $commands, string $accessMethod, int $port): string
    {
        $outputs = [];
        $client = $accessMethod === 'telnet' ? app(OltTelnetClient::class) : app(OltSshClient::class);

        try {
            if ($client instanceof OltTelnetClient) {
                $client->connect($oltDevice->host, $port, $oltDevice->username, $oltDevice->password, $oltDevice->enable_password);
            } else {
                $client->connect($oltDevice->host, $port, $oltDevice->username, $oltDevice->password);
            }

            foreach ($this->utilityContextCommands($this->contextCommands($oltDevice->read_context_commands)) as $contextCommand) {
                $outputs[] = $client->command($contextCommand);
            }

            foreach ($commands as $command) {
                $outputs[] = $client->command($command);
            }

            return implode("\n", $outputs);
        } finally {
            $client->close();
        }
    }

    private function shouldRetryUtilityOverTelnet(OltDevice $oltDevice, string $output): bool
    {
        return $oltDevice->access_method === 'ssh'
            && $this->usesHsgqEpon($oltDevice)
            && preg_match('/Unknown command:\s*show\s+(?:onu-info|black-onu)all/i', $output) === 1;
    }

    private function utilityContextCommands(array $commands): array
    {
        return array_values(array_filter(
            $commands,
            fn (string $command): bool => preg_match('/^enable$/i', trim($command)) === 1
        ));
    }

    private function baseContextCommands(array $commands, ?OltDevice $oltDevice = null): array
    {
        $baseCommands = array_values(array_filter(
            $commands,
            fn (string $command): bool => ! preg_match('/^interface\s+(?:epon|gpon)\s+(?:[1-9]|1[0-6])$/', strtolower(trim($command)))
        ));

        if (
            $oltDevice?->protocol_profile === 'hsgq_gpon'
            && ! collect($baseCommands)->contains(fn (string $command): bool => strtolower(trim($command)) === 'enable')
        ) {
            array_unshift($baseCommands, 'enable');
        }

        return $baseCommands;
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
                || preg_match('/^interface\s+(?:epon|gpon)\s+(?:[1-9]|1[0-6])$/', $normalized)
                || preg_match('/^interface\s+(?:onu|ont)\s+(?:[1-9]|1[0-6])\/\d{1,3}$/', $normalized)
            ) {
                continue;
            }

            return $command;
        }

        return null;
    }

    private function validateOlt(Request $request, ?OltDevice $oltDevice = null): array
    {
        $request->merge([
            'olt_access_username' => $request->input('olt_access_username', $request->input('username')),
            'olt_access_password' => $request->input('olt_access_password', $request->input('password')),
        ]);

        $passwordRules = $oltDevice ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'protocol_profile' => ['required', Rule::in(array_keys($this->protocolProfileOptions()))],
            'host' => ['required', 'string', 'max:255'],
            'access_method' => ['required', Rule::in(['ssh', 'telnet'])],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'olt_access_username' => ['required', 'string', 'max:255'],
            'olt_access_password' => $passwordRules,
            'enable_password' => ['nullable', 'string', 'max:255'],
            'snmp_enabled' => ['nullable', 'boolean'],
            'snmp_version' => ['required', Rule::in(['1', '2c'])],
            'snmp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'snmp_community' => ['nullable', 'string', 'max:255'],
            'snmp_timeout_ms' => ['required', 'integer', 'min:100', 'max:10000'],
            'snmp_retries' => ['required', 'integer', 'min:0', 'max:5'],
            'snmp_status_oid_template' => ['nullable', 'string', 'max:255'],
            'snmp_power_oid_template' => ['nullable', 'string', 'max:255'],
            'snmp_tx_power_oid_template' => ['nullable', 'string', 'max:255'],
            'snmp_power_divisor' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'read_context_commands' => ['nullable', 'string'],
            'pon_ports' => [
                'required',
                'string',
                'max:255',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->ponPorts((string) $value) === []
                    ? $fail('PON ports must be comma separated numbers from 1 to 8.')
                    : null,
            ],
            'onu_status_command' => ['required', 'string', 'max:255'],
            'onu_power_command' => ['required', 'string', 'max:255'],
            'onu_alarm_command' => ['nullable', 'string', 'max:255'],
            'onu_vlan_command' => ['nullable', 'string', 'max:255'],
            'onu_mac_command' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'auto_refresh_interval_hours' => ['nullable', 'integer', 'min:0', 'max:720'],
            'notes' => ['nullable', 'string'],
        ]) + [
            'snmp_enabled' => false,
        ];

        $data['auto_refresh_interval_hours'] = (int) ($data['auto_refresh_interval_hours'] ?? 24);

        $data['username'] = $data['olt_access_username'];
        $data['password'] = $data['olt_access_password'];
        unset($data['olt_access_username'], $data['olt_access_password']);

        return $data;
    }

    private function validateProtocolProfile(Request $request, ?OltProtocolProfile $profile = null): array
    {
        return $request->validate([
            'key' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('olt_protocol_profiles', 'key')->ignore($profile)],
            'label' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'pon_interface_command' => ['required', 'string', 'max:255'],
            'onu_context_command' => ['nullable', 'string', 'max:255'],
            'supports_vlan_polling' => ['nullable', 'boolean'],
            'supports_mac_polling' => ['nullable', 'boolean'],
            'default_read_context_commands' => ['nullable', 'string'],
            'default_onu_status_command' => ['nullable', 'string', 'max:255'],
            'default_onu_power_command' => ['nullable', 'string', 'max:255'],
            'default_onu_alarm_command' => ['nullable', 'string', 'max:255'],
            'default_onu_vlan_command' => ['nullable', 'string', 'max:255'],
            'default_onu_mac_command' => ['nullable', 'string', 'max:255'],
            'vlan_write_context_command' => ['nullable', 'string', 'max:255'],
            'vlan_write_command' => ['nullable', 'string', 'max:255'],
            'port_admin_context_command' => ['nullable', 'string', 'max:255'],
            'port_admin_command' => ['nullable', 'string', 'max:255'],
            'save_config_command' => ['nullable', 'string', 'max:255'],
        ]) + [
            'supports_vlan_polling' => false,
            'supports_mac_polling' => false,
        ];
    }

    private function protocolProfileOptions(): array
    {
        $profiles = OltProtocolProfile::query()
            ->orderBy('label')
            ->pluck('label', 'key')
            ->all();

        foreach (self::BUILT_IN_PROTOCOL_PROFILE_OPTIONS as $key => $label) {
            $profiles[$key] = $profiles[$key] ?? $label;
        }

        asort($profiles, SORT_STRING);

        return $profiles;
    }

    private function protocolProfileDefaults(): array
    {
        return OltProtocolProfile::query()
            ->get()
            ->mapWithKeys(fn (OltProtocolProfile $profile): array => [
                $profile->key => [
                    'brand' => $profile->brand,
                    'read_context_commands' => $profile->default_read_context_commands,
                    'onu_status_command' => $profile->default_onu_status_command,
                    'onu_power_command' => $profile->default_onu_power_command,
                    'onu_alarm_command' => $profile->default_onu_alarm_command,
                    'onu_vlan_command' => $profile->default_onu_vlan_command,
                    'onu_mac_command' => $profile->default_onu_mac_command,
                    'pon_ports' => implode(',', $this->defaultPonPorts($profile->key)),
                ],
            ])
            ->all();
    }

    private function profileDefaultValues(OltProtocolProfile $profile): array
    {
        return array_filter([
            'brand' => $profile->brand,
            'read_context_commands' => $profile->default_read_context_commands,
            'pon_ports' => implode(',', $this->defaultPonPorts($profile->key)),
            'onu_status_command' => $profile->default_onu_status_command,
            'onu_power_command' => $profile->default_onu_power_command,
            'onu_alarm_command' => $profile->default_onu_alarm_command,
            'onu_vlan_command' => $profile->default_onu_vlan_command,
            'onu_mac_command' => $profile->default_onu_mac_command,
        ], fn ($value): bool => $value !== null);
    }

    private function defaultPonPorts(string $profileKey): array
    {
        return range(1, 8);
    }

    private function profileCommandMismatches(OltDevice $oltDevice): array
    {
        $profile = $this->protocolProfile($oltDevice);

        if (! $profile) {
            return ['Protocol/profile is missing'];
        }

        $fields = [
            'read_context_commands' => ['Read context', 'default_read_context_commands'],
            'onu_status_command' => ['Status command', 'default_onu_status_command'],
            'onu_power_command' => ['Power command', 'default_onu_power_command'],
            'onu_alarm_command' => ['Detail/alarm command', 'default_onu_alarm_command'],
            'onu_vlan_command' => ['VLAN command', 'default_onu_vlan_command'],
            'onu_mac_command' => ['MAC command', 'default_onu_mac_command'],
        ];
        $mismatches = [];

        foreach ($fields as $deviceField => [$label, $profileField]) {
            $configured = str_replace(["\r\n", "\r"], "\n", trim((string) $oltDevice->{$deviceField}));
            $expected = str_replace(["\r\n", "\r"], "\n", trim((string) $profile->{$profileField}));

            if ($configured !== $expected) {
                $mismatches[] = $label;
            }
        }

        $configuredPorts = $this->ponPorts($oltDevice->pon_ports);
        if ($configuredPorts !== $this->defaultPonPorts($profile->key)) {
            $mismatches[] = 'PON list';
        }

        return $mismatches;
    }

    private function applyOnuSort($query, Request $request): void
    {
        $sort = (string) $request->query('sort', 'olt');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $columns = [
            'olt' => 'olt_name',
            'name' => 'name',
            'serial' => 'mac_address',
            'mac' => 'mac_address',
            'device_macs' => 'learned_macs',
            'type' => 'onu_type',
            'status' => 'status',
            'power' => 'rx_power_dbm',
            'vlans' => 'port_vlans',
            'last_register' => 'last_registered_at',
            'last_deregister' => 'last_deregistered_at',
            'last_poll' => 'last_live_polled_at',
            'description' => 'description',
        ];

        if ($sort === 'pon_onu') {
            $query->orderBy('pon_port', $direction)->orderBy('onu_id', $direction);

            return;
        }

        $query
            ->orderBy($columns[$sort] ?? 'pon_port', $direction)
            ->orderBy('pon_port')
            ->orderBy('onu_id');
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

    private function firstUnsafeReadOrContextCommand(array $commands): ?string
    {
        foreach ($commands as $command) {
            $normalized = strtolower(trim((string) $command));

            if (preg_match('/[;&|`$]/', $normalized)) {
                return $command;
            }

            if (
                $normalized === 'enable'
                || $normalized === 'config'
                || $normalized === 'configure'
                || $normalized === 'exit'
                || preg_match('/^interface\s+(?:epon|gpon)\s+(?:[1-9]|1[0-6])$/', $normalized)
                || preg_match('/^interface\s+(?:onu|ont)\s+(?:[1-9]|1[0-6])\/\d{1,3}$/', $normalized)
                || preg_match('/^(show|display)\b/', $normalized)
            ) {
                continue;
            }

            return $command;
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
            'onu_type',
            'status',
            'name',
            'description',
            'rx_power_dbm',
            'tx_power_dbm',
            'distance_m',
            'raw_live_output',
            'raw_interface_config',
            'port_vlans',
            'ethernet_port_count',
            'learned_macs',
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

    private function onuRecordKeys(array $records, int $ponPort): array
    {
        $keys = [];

        foreach ($records as $record) {
            if (($record['pon_port'] ?? null) !== $ponPort || ! isset($record['onu_id'])) {
                continue;
            }

            $keys[] = $this->onuRecordKey($ponPort, (int) $record['onu_id']);
        }

        return array_values(array_unique($keys));
    }

    private function filterAuthoritativeLiveRecords(array $records, array $authoritativeKeysByPon): array
    {
        return array_values(array_filter($records, function (array $record) use ($authoritativeKeysByPon): bool {
            if (! isset($record['pon_port'], $record['onu_id'])) {
                return false;
            }

            $ponPort = (int) $record['pon_port'];
            $authoritativeKeys = $authoritativeKeysByPon[$ponPort] ?? [];

            if ($authoritativeKeys === []) {
                return true;
            }

            return in_array($this->onuRecordKey($ponPort, (int) $record['onu_id']), $authoritativeKeys, true);
        }));
    }

    private function deleteStaleLiveOnus(OltDevice $oltDevice, array $authoritativeKeysByPon): int
    {
        $deleted = 0;

        foreach ($authoritativeKeysByPon as $ponPort => $keys) {
            if ($keys === []) {
                continue;
            }

            $currentOnuIds = array_map(
                fn (string $key): int => (int) substr($key, strpos($key, '/') + 1),
                $keys
            );

            $deleted += OltOnu::query()
                ->where('olt_device_id', $oltDevice->id)
                ->where('pon_port', (int) $ponPort)
                ->whereNotIn('onu_id', $currentOnuIds)
                ->delete();
        }

        return $deleted;
    }

    private function onuRecordKey(int $ponPort, int $onuId): string
    {
        return $ponPort.'/'.$onuId;
    }

    private function ponInterfaceCommand(OltDevice $oltDevice, int $ponPort): string
    {
        $template = $this->protocolProfile($oltDevice)?->pon_interface_command ?: 'interface epon {pon_port}';

        return str_replace('{pon_port}', (string) $ponPort, $template);
    }

    private function isGlobalOnuPollingCommand(string $command): bool
    {
        return str_contains($command, ' all') && ! str_contains($command, '{onu_id}');
    }

    private function optionalOltCommand($client, string $command): string
    {
        try {
            return $client->command($command);
        } catch (Throwable $exception) {
            return 'Optional OLT command failed: '.$command.' - '.$exception->getMessage();
        }
    }

    private function optionalWriteOltCommand($client, string $command): string
    {
        $output = $client->command($command);

        if ($this->hasOltCommandError($output)) {
            return 'Optional OLT command failed: '.$command.' - '.$this->summarizeOltOutput($output);
        }

        return $output;
    }

    private function writeOnuVlanToOlt(OltOnu $oltOnu, OltProtocolProfile $profile, int $vlan, array $portVlans): string
    {
        $commands = [];
        $ports = $this->editableVlanPorts($portVlans);

        foreach ($ports as $port) {
            $hasContext = trim((string) $profile->vlan_write_context_command) !== '';

            if ($hasContext) {
                $commands[] = $this->fillOltCommandTemplate($profile->vlan_write_context_command, $oltOnu, $vlan, $port);
            }

            $commands[] = $this->fillOltCommandTemplate($profile->vlan_write_command, $oltOnu, $vlan, $port);

            if ($hasContext) {
                $commands[] = 'exit';
            }
        }

        return $this->runOltWriteCommands($oltOnu->oltDevice, $commands);
    }

    private function writeOnuTransparentVlanToOlt(OltOnu $oltOnu, OltProtocolProfile $profile, array $portVlans): string
    {
        return $this->runOltWriteCommands(
            $oltOnu->oltDevice,
            $this->transparentVlanCommands($oltOnu, $profile, $portVlans)
        );
    }

    private function transparentVlanCommands(OltOnu $oltOnu, OltProtocolProfile $profile, array $portVlans): array
    {
        $commands = [];

        foreach ($this->editableVlanPorts($portVlans) as $port) {
            $commands[] = $this->fillOltCommandTemplate($profile->vlan_write_context_command, $oltOnu, 1, $port);
            $commands[] = "port-vlan {$port} mode transparent";
            $commands[] = 'exit';
        }

        return $commands;
    }

    private function ethernetPortStateCommands(OltOnu $oltOnu, OltProtocolProfile $profile, int $port, string $state): array
    {
        if ($oltOnu->oltDevice && $this->usesHsgqEpon($oltOnu->oltDevice)) {
            return [
                'interface onu '.$oltOnu->pon_port.'/'.$oltOnu->onu_id,
                $state === 'disable' ? 'port-shutdown '.$port : 'no port-shutdown '.$port,
                'exit',
                $profile->save_config_command ?: 'save',
            ];
        }

        $replace = fn (string $template): string => str_replace(
            ['{pon_port}', '{onu_id}', '{port}', '{state}'],
            [(string) $oltOnu->pon_port, (string) $oltOnu->onu_id, (string) $port, $state],
            $template
        );
        $commands = [];

        if (trim((string) $profile->port_admin_context_command) !== '') {
            $commands[] = $replace($profile->port_admin_context_command);
        }

        $commands[] = $replace($profile->port_admin_command);

        if (trim((string) $profile->port_admin_context_command) !== '') {
            $commands[] = 'exit';
        }

        if ($profile->save_config_command) {
            $commands[] = $profile->save_config_command;
        }

        return $commands;
    }

    private function editableVlanPorts(array $portVlans): array
    {
        $ports = [];

        foreach ($portVlans as $portVlan) {
            $port = (int) ($portVlan['port'] ?? 1);

            if ($port >= 1 && $port <= 8) {
                $ports[] = $port;
            }
        }

        return array_values(array_unique($ports ?: [1]));
    }

    private function fillOltCommandTemplate(string $template, OltOnu $oltOnu, int $vlan, int $port): string
    {
        $meta = $this->vlanWriteMeta($oltOnu);
        $portPath = $this->gponVlanPortPath($oltOnu, $port);

        return str_replace(
            ['{pon_port}', '{onu_id}', '{port}', '{port_path}', '{vlan}', '{priority}', '{service_port}', '{gemport}'],
            [(string) $oltOnu->pon_port, (string) $oltOnu->onu_id, (string) $port, $portPath, (string) $vlan, '0', (string) $meta['service_port'], (string) $meta['gemport']],
            $template
        );
    }

    private function gponVlanPortPath(OltOnu $oltOnu, int $port): string
    {
        $onuType = strtoupper(trim((string) $oltOnu->onu_type));

        if ($onuType === '' && preg_match('/^\s*ISP\s+ONU\s+Type\s*:\s*(\S+)/mi', (string) $oltOnu->raw_live_output, $match)) {
            $onuType = strtoupper($match[1]);
        }

        return str_contains($onuType, 'HGU') ? 'veip' : 'eth '.$port;
    }

    private function vlanWriteMeta(OltOnu $oltOnu): array
    {
        foreach ($oltOnu->port_vlans ?: [] as $portVlan) {
            if (isset($portVlan['service_port'])) {
                return [
                    'service_port' => (int) $portVlan['service_port'],
                    'gemport' => (int) ($portVlan['gemport'] ?? 1),
                ];
            }
        }

        foreach ($oltOnu->learned_macs ?: [] as $learnedMac) {
            if (isset($learnedMac['service_port'])) {
                return [
                    'service_port' => (int) $learnedMac['service_port'],
                    'gemport' => (int) ($learnedMac['gemport'] ?? 1),
                ];
            }
        }

        return ['service_port' => 0, 'gemport' => 1];
    }

    private function runOltWriteCommands(OltDevice $oltDevice, array $commands): string
    {
        $commands = array_values(array_filter(array_map('trim', $commands)));

        if ($commands === []) {
            return '';
        }

        $accessMethod = $this->writeAccessMethod($oltDevice);
        $port = $accessMethod === 'telnet' && $oltDevice->access_method !== 'telnet'
            ? 23
            : (int) $oltDevice->port;

        try {
            $output = $this->runOltWriteCommandsWithMethod($oltDevice, $commands, $accessMethod, $port);
            $this->markOltConnectionSucceeded($oltDevice);

            return $output;
        } catch (Throwable $exception) {
            if ($this->shouldRetryWriteOverTelnet($oltDevice, $exception)) {
                try {
                    $output = $this->runOltWriteCommandsWithMethod($oltDevice, $commands, 'telnet', 23);
                    $this->markOltConnectionSucceeded($oltDevice);

                    return $output;
                } catch (Throwable $telnetException) {
                    $exception = $telnetException;
                }
            }

            $this->markOltOperationFailed($oltDevice, $exception);

            throw $exception;
        }
    }

    private function writeAccessMethod(OltDevice $oltDevice): string
    {
        return $oltDevice->access_method;
    }

    private function usesHsgqEpon(OltDevice $oltDevice): bool
    {
        return strtolower(trim((string) $oltDevice->protocol_profile)) === 'hsgq_epon';
    }

    private function runOltWriteCommandsWithMethod(OltDevice $oltDevice, array $commands, string $accessMethod, int $port): string
    {
        $outputs = [];
        $client = $accessMethod === 'telnet' ? app(OltTelnetClient::class) : app(OltSshClient::class);

        try {
            if ($client instanceof OltTelnetClient) {
                $client->connect($oltDevice->host, $port, $oltDevice->username, $oltDevice->password, $oltDevice->enable_password);
            } else {
                $client->connect($oltDevice->host, $port, $oltDevice->username, $oltDevice->password);
            }

            foreach ($this->baseContextCommands($this->contextCommands($oltDevice->read_context_commands), $oltDevice) as $contextCommand) {
                $outputs[] = $client->command($contextCommand);
            }

            foreach ($commands as $command) {
                $outputs[] = $this->runOltWriteCommand($client, $command);
            }

            return implode("\n", $outputs);
        } finally {
            $client->close();
        }
    }

    private function runOltWriteCommand($client, string $command): string
    {
        if (str_contains($command, '||')) {
            return $this->runFirstAcceptedOltCommand($client, $command);
        }

        $isOptional = str_starts_with($command, '?');
        $command = $isOptional ? ltrim(substr($command, 1)) : $command;

        $output = $isOptional
            ? $this->optionalWriteOltCommand($client, $command)
            : $client->command($command);

        if (! $isOptional && $this->hasOltCommandError($output)) {
            throw new \RuntimeException('OLT rejected command "'.$command.'": '.$this->summarizeOltOutput($output));
        }

        return $output;
    }

    private function shouldRetryWriteOverTelnet(OltDevice $oltDevice, Throwable $exception): bool
    {
        return $oltDevice->access_method === 'ssh'
            && $this->usesHsgqEpon($oltDevice)
            && preg_match('/Unknown command:.*(?:bind-onu|blacklist|port-vlan)\S+/is', $exception->getMessage()) === 1;
    }

    private function runFirstAcceptedOltCommand($client, string $commandAlternatives): string
    {
        $outputs = [];
        $lastCommand = '';

        foreach (array_filter(array_map('trim', explode('||', $commandAlternatives))) as $command) {
            $lastCommand = $command;
            $output = $client->command($command);
            $outputs[] = $output;

            if (! $this->hasOltCommandError($output)) {
                return implode("\n", $outputs);
            }
        }

        throw new \RuntimeException('OLT rejected command "'.$lastCommand.'": '.$this->summarizeOltOutput(end($outputs) ?: ''));
    }

    private function runOltReadCommands(OltDevice $oltDevice, array $commands): string
    {
        $commands = array_values(array_filter(array_map('trim', $commands)));
        $accessMethod = $this->readAccessMethod($oltDevice);
        $port = $accessMethod === 'telnet' && $oltDevice->access_method !== 'telnet'
            ? 23
            : (int) $oltDevice->port;
        $client = $accessMethod === 'telnet' ? app(OltTelnetClient::class) : app(OltSshClient::class);

        try {
            if ($client instanceof OltTelnetClient) {
                $client->connect($oltDevice->host, $port, $oltDevice->username, $oltDevice->password, $oltDevice->enable_password);
            } else {
                $client->connect($oltDevice->host, $port, $oltDevice->username, $oltDevice->password);
            }

            foreach ($this->baseContextCommands($this->contextCommands($oltDevice->read_context_commands), $oltDevice) as $contextCommand) {
                $outputs[] = $client->command($contextCommand);
            }

            foreach ($commands as $command) {
                $outputs[] = $client->command($command);
            }

            $output = implode("\n", $outputs ?? []);
            $this->markOltConnectionSucceeded($oltDevice);

            return $output;
        } catch (Throwable $exception) {
            $this->markOltOperationFailed($oltDevice, $exception);

            throw $exception;
        } finally {
            $client->close();
        }
    }

    private function markOltConnectionSucceeded(OltDevice $oltDevice): void
    {
        $oltDevice->update([
            'last_error' => null,
            'last_polled_at' => now(),
        ]);
    }

    private function testOltConnection(OltDevice $oltDevice): void
    {
        $client = $oltDevice->access_method === 'telnet'
            ? app(OltTelnetClient::class)
            : app(OltSshClient::class);

        try {
            if ($client instanceof OltTelnetClient) {
                $client->connect(
                    $oltDevice->host,
                    (int) $oltDevice->port,
                    $oltDevice->username,
                    $oltDevice->password,
                    $oltDevice->enable_password,
                );
            } else {
                $client->connect(
                    $oltDevice->host,
                    (int) $oltDevice->port,
                    $oltDevice->username,
                    $oltDevice->password,
                );
            }

            $this->markOltConnectionSucceeded($oltDevice);
        } catch (Throwable $exception) {
            $this->markOltOperationFailed($oltDevice, $exception);

            throw $exception;
        } finally {
            $client->close();
        }
    }

    private function markOltOperationFailed(OltDevice $oltDevice, Throwable $exception): void
    {
        $message = Utf8Text::clean($exception->getMessage()) ?? 'Unknown error';
        $values = ['last_error' => $message];

        if (! $this->isOltConnectionFailure($message)) {
            $values['last_polled_at'] = now();
        }

        $oltDevice->update($values);
    }

    private function isOltConnectionFailure(string $message): bool
    {
        return preg_match('/(?:authentication|login failed|credential|cannot connect|connection (?:failed|refused|reset)|socket|timed? out|timeout|host unreachable|network is unreachable|no route to host|not connected|broken pipe)/i', $message) === 1;
    }

    private function denyListDeleteCommands(int $ponPort, string $serial, ?OltProtocolProfile $profile): array
    {
        return [
            "interface epon {$ponPort}",
            "blacklist delete mac {$serial}",
            'exit',
            $profile?->save_config_command ?: 'save',
        ];
    }

    private function authorizeOnuCommands(OltDevice $oltDevice, ?OltProtocolProfile $profile, array $data): array
    {
        $ponPort = (int) $data['pon_port'];
        $onuId = (int) $data['onu_id'];
        $vlan = (int) $data['vlan'];
        $ethernetPort = (int) $data['ethernet_port'];
        $serial = $data['serial'];
        $name = $this->oltQuoted($data['name']);

        if ($oltDevice->protocol_profile === 'hsgq_gpon') {
            return array_values(array_filter([
                "interface gpon {$ponPort}",
                "ont add {$onuId} sn-auth {$serial} desc {$name} omci ont-lineprofile-id 0 ont-srvprofile-id 0 || ont add {$onuId} sn-auth {$serial} desc {$name} ont-lineprofile-id 0 ont-srvprofile-id 0 || ont add {$onuId} sn-auth {$serial} omci ont-lineprofile-id 0 ont-srvprofile-id 0",
                "?ont modify {$ponPort} {$onuId} desc {$name}",
                "ont port native-vlan {$onuId} eth {$ethernetPort} vlan {$vlan} 0",
                'exit',
                $profile?->save_config_command ?: 'save',
            ]));
        }

        return array_values(array_filter([
            "interface epon {$ponPort}",
            ($data['source_type'] ?? null) === 'deny' ? "?blacklist delete mac {$serial}" : null,
            "bind-onu {$onuId} mac {$serial} onu-type 1ge name {$name}",
            'exit',
            "interface onu {$ponPort}/{$onuId}",
            "port-vlan {$ethernetPort} mode tag {$vlan} pri 0",
            'exit',
            $profile?->save_config_command ?: 'save',
        ]));
    }

    private function authorizeAutoEponOnuCommands(array $data): array
    {
        $ponPort = (int) $data['pon_port'];
        $serial = $data['serial'];
        $name = $this->oltQuoted($data['name']);

        return array_values(array_filter([
            "interface epon {$ponPort}",
            ($data['source_type'] ?? null) === 'deny' ? "?blacklist delete mac {$serial}" : null,
            "bind-onu mac {$serial} onu-type 1ge name {$name}",
            'show onu-info all',
            'exit',
        ]));
    }

    private function eponVlanCommands(?OltProtocolProfile $profile, array $data): array
    {
        $ponPort = (int) $data['pon_port'];
        $onuId = (int) $data['onu_id'];
        $vlan = (int) $data['vlan'];
        $ethernetPort = (int) $data['ethernet_port'];

        return [
            "interface onu {$ponPort}/{$onuId}",
            "port-vlan {$ethernetPort} mode tag {$vlan} pri 0",
            'exit',
            $profile?->save_config_command ?: 'save',
        ];
    }

    private function oltQuoted(string $value): string
    {
        return '"'.str_replace('"', '', trim($value)).'"';
    }

    private function hasOltCommandError(string $output): bool
    {
        return (bool) preg_match('/\b(error|unknown command|command incomplete|fail|failed)\b/i', $output)
            && ! preg_match('/repeat operation/i', $output);
    }

    private function summarizeOltOutput(string $output): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', Utf8Text::clean($output) ?? '') ?: [])));

        return mb_substr(implode(' ', array_slice($lines, -3)), 0, 240);
    }

    private function onuContextCommand(OltDevice $oltDevice, int $ponPort, int $onuId): string
    {
        $template = $this->protocolProfile($oltDevice)?->onu_context_command ?: 'interface onu {pon_port}/{onu_id}';

        return str_replace(['{pon_port}', '{onu_id}'], [(string) $ponPort, (string) $onuId], $template);
    }

    private function protocolProfile(OltDevice $oltDevice): ?OltProtocolProfile
    {
        return OltProtocolProfile::query()
            ->where('key', $oltDevice->protocol_profile)
            ->first();
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

    private function onuIdsForVlanRefresh(array $records, OltDevice $oltDevice, int $ponPort, bool $refreshExisting): array
    {
        $onuIds = [];

        foreach ($records as $record) {
            if (($record['pon_port'] ?? null) !== $ponPort || ! isset($record['onu_id'])) {
                continue;
            }

            if ($refreshExisting) {
                $onuIds[] = (int) $record['onu_id'];

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
