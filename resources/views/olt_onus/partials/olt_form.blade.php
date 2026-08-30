<form method="post" action="{{ $action }}" class="card">
    @csrf
    @if (($method ?? 'post') !== 'post')
        @method($method)
    @endif

    <div class="form-grid">
        <div>
            <label for="name">OLT Name</label>
            <input id="name" name="name" value="{{ old('name', $oltDevice->name) }}" required>
        </div>
        <div>
            <label for="brand">OLT Brand</label>
            <input id="brand" name="brand" value="{{ old('brand', $oltDevice->brand ?: 'HSGQ') }}" required>
        </div>
        <div>
            <label for="protocol_profile">OLT Protocol/Profile</label>
            @php
                $resolvedProtocolProfiles = is_array($protocolProfiles ?? null) ? $protocolProfiles : [];
                $resolvedProfileDefaults = is_array($profileDefaults ?? null) ? $profileDefaults : [];
                $fallbackProtocolProfiles = [
                    'hsgq_epon' => 'HSGQ EPON OLT',
                    'hsgq_gpon' => 'HSGQ GPON OLT',
                ];
                $fallbackProfileDefaults = [
                    'hsgq_epon' => [
                        'brand' => 'HSGQ',
                        'read_context_commands' => "enable\nconfig",
                        'onu_status_command' => 'show onu-info all',
                        'onu_power_command' => 'show optical-info',
                        'onu_alarm_command' => 'show onu-info-alarm {onu_id}',
                        'onu_vlan_command' => 'show port-vlan',
                        'onu_mac_command' => 'show mac-address epon all',
                        'pon_ports' => '1,2,3,4,5,6,7,8',
                    ],
                    'hsgq_gpon' => [
                        'brand' => 'HSGQ',
                        'read_context_commands' => "enable\nconfig",
                        'onu_status_command' => 'show ont-info all',
                        'onu_power_command' => 'show ont-optical all',
                        'onu_alarm_command' => 'show ont-info {onu_id}',
                        'onu_vlan_command' => 'show service-port all',
                        'onu_mac_command' => 'show mac-address all',
                        'pon_ports' => '1,2,3,4,5,6,7,8',
                    ],
                ];

                $resolvedProtocolProfiles = array_replace($fallbackProtocolProfiles, $resolvedProtocolProfiles);
                $resolvedProfileDefaults = array_replace_recursive($fallbackProfileDefaults, $resolvedProfileDefaults);
            @endphp
            <select id="protocol_profile" name="protocol_profile" required>
                @foreach ($resolvedProtocolProfiles as $value => $label)
                    <option value="{{ $value }}" @selected(old('protocol_profile', $oltDevice->protocol_profile ?: 'hsgq_epon') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn light" id="load-profile-defaults" type="button" style="margin-top:8px">Load Profile Defaults</button>
            <div class="muted" style="margin-top:8px; color:#173a31; font-weight:700">Use this after changing EPON/GPON profile so incompatible polling commands are replaced.</div>
        </div>
        <div>
            <label for="host">Host/IP</label>
            <input id="host" name="host" value="{{ old('host', $oltDevice->host) }}" required>
        </div>
        <div>
            <label for="access_method">Access Method</label>
            <select id="access_method" name="access_method" required>
                <option value="ssh" @selected(old('access_method', $oltDevice->access_method ?: 'ssh') === 'ssh')>SSH read-only</option>
                <option value="telnet" @selected(old('access_method', $oltDevice->access_method) === 'telnet')>Telnet read-only</option>
            </select>
        </div>
        <div>
            <label for="port">Port</label>
            <input id="port" name="port" type="number" min="1" max="65535" value="{{ old('port', $oltDevice->port ?: 22) }}" required>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $oltDevice->status ?: 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $oltDevice->status) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div>
            <label for="auto_refresh_interval_hours">Auto-refresh every (hours)</label>
            <input id="auto_refresh_interval_hours" name="auto_refresh_interval_hours" type="number" min="0" max="720"
                   value="{{ old('auto_refresh_interval_hours', $oltDevice->auto_refresh_interval_hours ?? 24) }}">
            <div class="muted" style="margin-top:6px">Background drip refresh, one OLT per hour when idle. 0 = off.</div>
        </div>
        <div>
            <label for="olt_access_username">Username</label>
            <input id="olt_access_username" name="olt_access_username" value="{{ old('olt_access_username', old('username', $oltDevice->username)) }}" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" required>
        </div>
        <div>
            <label for="olt_access_password">Password</label>
            <input id="olt_access_password" name="olt_access_password" type="password" autocomplete="new-password" @required(! $oltDevice->exists)>
            @if ($oltDevice->exists)
                <div class="muted" style="margin-top:6px">Leave empty to keep the current password.</div>
            @endif
        </div>
        <div>
            <label for="enable_password">Enable Password</label>
            <input id="enable_password" name="enable_password" type="password" autocomplete="new-password">
            <div class="muted" style="margin-top:6px">Optional. Leave empty to keep current value when editing.</div>
        </div>
        <div class="full" style="border-top:1px solid var(--line); padding-top:14px; margin-top:4px">
            <label style="display:flex; gap:8px; align-items:center">
                <input name="snmp_enabled" type="hidden" value="0">
                <input id="snmp_enabled" name="snmp_enabled" type="checkbox" value="1" @checked(old('snmp_enabled', $oltDevice->snmp_enabled)) style="width:auto">
                Use SNMP for fast row Update Now
            </label>
            <div class="muted" style="margin-top:6px">SNMP is tried first for single ONU status/power. If it is not configured or fails, CLI refresh still runs.</div>
        </div>
        <div>
            <label for="snmp_version">SNMP Version</label>
            <select id="snmp_version" name="snmp_version">
                <option value="2c" @selected(old('snmp_version', $oltDevice->snmp_version ?: '2c') === '2c')>v2c</option>
                <option value="1" @selected(old('snmp_version', $oltDevice->snmp_version) === '1')>v1</option>
            </select>
        </div>
        <div>
            <label for="snmp_port">SNMP Port</label>
            <input id="snmp_port" name="snmp_port" type="number" min="1" max="65535" value="{{ old('snmp_port', $oltDevice->snmp_port ?: 161) }}">
        </div>
        <div>
            <label for="snmp_community">SNMP Community</label>
            <input id="snmp_community" name="snmp_community" type="password">
            @if ($oltDevice->exists && $oltDevice->snmp_community)
                <div class="muted" style="margin-top:6px">Leave empty to keep the current SNMP community.</div>
            @endif
        </div>
        <div>
            <label for="snmp_timeout_ms">SNMP Timeout MS</label>
            <input id="snmp_timeout_ms" name="snmp_timeout_ms" type="number" min="100" max="10000" value="{{ old('snmp_timeout_ms', $oltDevice->snmp_timeout_ms ?: 800) }}">
        </div>
        <div>
            <label for="snmp_retries">SNMP Retries</label>
            <input id="snmp_retries" name="snmp_retries" type="number" min="0" max="5" value="{{ old('snmp_retries', $oltDevice->snmp_retries ?? 1) }}">
        </div>
        <div class="full">
            <label for="snmp_status_oid_template">SNMP Status OID Template</label>
            <input id="snmp_status_oid_template" name="snmp_status_oid_template" value="{{ old('snmp_status_oid_template', $oltDevice->snmp_status_oid_template) }}" placeholder=".1.3.6.1.x.x.{pon_port}.{onu_id}">
            <div class="muted" style="margin-top:6px">Supported placeholders: {pon_port}, {onu_id}, {pon_onu}, {pon_onu_dot}.</div>
        </div>
        <div class="full">
            <label for="snmp_power_oid_template">SNMP Rx Power OID Template</label>
            <input id="snmp_power_oid_template" name="snmp_power_oid_template" value="{{ old('snmp_power_oid_template', $oltDevice->snmp_power_oid_template) }}" placeholder=".1.3.6.1.x.x.{pon_port}.{onu_id}">
        </div>
        <div class="full">
            <label for="snmp_tx_power_oid_template">SNMP Tx Power OID Template</label>
            <input id="snmp_tx_power_oid_template" name="snmp_tx_power_oid_template" value="{{ old('snmp_tx_power_oid_template', $oltDevice->snmp_tx_power_oid_template) }}" placeholder=".1.3.6.1.x.x.{pon_port}.{onu_id}">
            <div class="muted" style="margin-top:6px">Optional — ONU upstream transmit power. Uses the same divisor. Leave blank if the OLT has no Tx OID.</div>
        </div>
        <div>
            <label for="snmp_power_divisor">SNMP Power Divisor</label>
            <input id="snmp_power_divisor" name="snmp_power_divisor" type="number" min="0.01" step="0.01" value="{{ old('snmp_power_divisor', $oltDevice->snmp_power_divisor ?: 1) }}">
            <div class="muted" style="margin-top:6px">Use 10 when raw -238 means -23.8 dBm.</div>
        </div>
        <div class="full">
            <label for="read_context_commands">Read Context Commands</label>
            <textarea id="read_context_commands" name="read_context_commands" rows="3" placeholder="enable&#10;config">{{ old('read_context_commands', $oltDevice->read_context_commands) }}</textarea>
            <div class="muted" style="margin-top:6px">Allowed only for CLI navigation before show commands. PON interfaces are selected below.</div>
        </div>
        <div>
            <label for="pon_ports">PON Ports To Poll</label>
            <input id="pon_ports" name="pon_ports" value="{{ old('pon_ports', $oltDevice->pon_ports) }}" required>
            <div class="muted" style="margin-top:6px">Comma separated, allowed 1-8.</div>
        </div>
        <div>
            <label for="onu_status_command">ONU Status/List Command</label>
            <input id="onu_status_command" name="onu_status_command" value="{{ old('onu_status_command', $oltDevice->onu_status_command) }}" required>
        </div>
        <div>
            <label for="onu_power_command">ONU Optical Power Command</label>
            <input id="onu_power_command" name="onu_power_command" value="{{ old('onu_power_command', $oltDevice->onu_power_command) }}" required>
        </div>
        <div>
            <label for="onu_alarm_command">ONU Alarm History Command</label>
            <input id="onu_alarm_command" name="onu_alarm_command" value="{{ old('onu_alarm_command', $oltDevice->onu_alarm_command) }}">
            <div class="muted" style="margin-top:6px">Use {onu_id} where the ONU number should be inserted.</div>
        </div>
        <div>
            <label for="onu_vlan_command">ONU VLAN Command</label>
            <input id="onu_vlan_command" name="onu_vlan_command" value="{{ old('onu_vlan_command', $oltDevice->onu_vlan_command) }}">
            <div class="muted" style="margin-top:6px">For HSGQ EPON this runs inside interface onu PON/ONU context.</div>
        </div>
        <div>
            <label for="onu_mac_command">ONU Learned MAC Command</label>
            <input id="onu_mac_command" name="onu_mac_command" value="{{ old('onu_mac_command', $oltDevice->onu_mac_command) }}">
            <div class="muted" style="margin-top:6px">For HSGQ EPON use show mac-address epon all.</div>
        </div>
        <div class="full">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes">{{ old('notes', $oltDevice->notes) }}</textarea>
        </div>
    </div>

    <div class="actions" style="margin-top:16px">
        <button class="btn" type="submit">{{ $submitLabel }}</button>
    </div>
</form>
<script>
document.getElementById('access_method').addEventListener('change', event => {
    const port = document.getElementById('port');
    if (!port.dataset.touched) {
        port.value = event.target.value === 'telnet' ? '23' : '22';
    }
});
document.getElementById('port').addEventListener('input', event => {
    event.target.dataset.touched = '1';
});
const profileDefaults = @json($resolvedProfileDefaults);

document.getElementById('protocol_profile').addEventListener('change', event => {
    applyOltProfileDefaults(event.target.value);
});

document.getElementById('load-profile-defaults').addEventListener('click', () => {
    applyOltProfileDefaults(document.getElementById('protocol_profile').value);
});

function applyOltProfileDefaults(profileKey) {
    const defaults = profileDefaults[profileKey];
    if (!defaults) {
        return;
    }

    document.getElementById('brand').value = defaults.brand || '';
    document.getElementById('read_context_commands').value = defaults.read_context_commands || '';
    document.getElementById('pon_ports').value = defaults.pon_ports || '';
    document.getElementById('onu_status_command').value = defaults.onu_status_command || '';
    document.getElementById('onu_power_command').value = defaults.onu_power_command || '';
    document.getElementById('onu_alarm_command').value = defaults.onu_alarm_command || '';
    document.getElementById('onu_vlan_command').value = defaults.onu_vlan_command || '';
    document.getElementById('onu_mac_command').value = defaults.onu_mac_command || '';
}
</script>
