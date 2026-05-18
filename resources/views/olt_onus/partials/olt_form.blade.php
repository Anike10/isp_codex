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
            <select id="protocol_profile" name="protocol_profile" required>
                @foreach ($protocolProfiles as $value => $label)
                    <option value="{{ $value }}" @selected(old('protocol_profile', $oltDevice->protocol_profile ?: 'hsgq_epon') === $value)>{{ $label }}</option>
                @endforeach
            </select>
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
            <label for="username">Username</label>
            <input id="username" name="username" value="{{ old('username', $oltDevice->username) }}" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" @required(! $oltDevice->exists)>
            @if ($oltDevice->exists)
                <div class="muted" style="margin-top:6px">Leave empty to keep the current password.</div>
            @endif
        </div>
        <div>
            <label for="enable_password">Enable Password</label>
            <input id="enable_password" name="enable_password" type="password">
            <div class="muted" style="margin-top:6px">Optional. Leave empty to keep current value when editing.</div>
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
document.getElementById('protocol_profile').addEventListener('change', event => {
    if (event.target.value !== 'hsgq_epon') {
        return;
    }

    document.getElementById('brand').value ||= 'HSGQ';
    document.getElementById('read_context_commands').value ||= 'enable\nconfig';
    document.getElementById('onu_status_command').value ||= 'show onu-info all';
    document.getElementById('onu_power_command').value ||= 'show optical-info';
    document.getElementById('onu_alarm_command').value ||= 'show onu-info-alarm {onu_id}';
    document.getElementById('onu_vlan_command').value ||= 'show port-vlan';
});
</script>
