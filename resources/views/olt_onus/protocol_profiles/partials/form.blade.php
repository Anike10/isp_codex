<form method="post" action="{{ $action }}" class="card">
    @csrf
    @if (($method ?? 'post') !== 'post')
        @method($method)
    @endif

    <div class="form-grid">
        <div>
            <label for="key">Profile Key</label>
            <input id="key" name="key" value="{{ old('key', $profile->key) }}" required>
        </div>
        <div>
            <label for="label">Label</label>
            <input id="label" name="label" value="{{ old('label', $profile->label) }}" required>
        </div>
        <div>
            <label for="brand">Brand</label>
            <input id="brand" name="brand" value="{{ old('brand', $profile->brand) }}">
        </div>
        <div>
            <label for="pon_interface_command">PON Interface Command</label>
            <input id="pon_interface_command" name="pon_interface_command" value="{{ old('pon_interface_command', $profile->pon_interface_command) }}" required>
        </div>
        <div>
            <label for="onu_context_command">ONU Context Command</label>
            <input id="onu_context_command" name="onu_context_command" value="{{ old('onu_context_command', $profile->onu_context_command) }}">
        </div>
        <div class="actions" style="align-items:flex-end">
            <label style="margin:0"><input type="checkbox" name="supports_vlan_polling" value="1" style="width:auto" @checked(old('supports_vlan_polling', $profile->supports_vlan_polling))> VLAN polling</label>
            <label style="margin:0"><input type="checkbox" name="supports_mac_polling" value="1" style="width:auto" @checked(old('supports_mac_polling', $profile->supports_mac_polling))> MAC polling</label>
        </div>
        <div class="full">
            <label for="default_read_context_commands">Default Read Context Commands</label>
            <textarea id="default_read_context_commands" name="default_read_context_commands" rows="3">{{ old('default_read_context_commands', $profile->default_read_context_commands) }}</textarea>
        </div>
        <div>
            <label for="default_onu_status_command">Default Status Command</label>
            <input id="default_onu_status_command" name="default_onu_status_command" value="{{ old('default_onu_status_command', $profile->default_onu_status_command) }}">
        </div>
        <div>
            <label for="default_onu_power_command">Default Power Command</label>
            <input id="default_onu_power_command" name="default_onu_power_command" value="{{ old('default_onu_power_command', $profile->default_onu_power_command) }}">
        </div>
        <div>
            <label for="default_onu_alarm_command">Default Alarm Command</label>
            <input id="default_onu_alarm_command" name="default_onu_alarm_command" value="{{ old('default_onu_alarm_command', $profile->default_onu_alarm_command) }}">
        </div>
        <div>
            <label for="default_onu_vlan_command">Default VLAN Command</label>
            <input id="default_onu_vlan_command" name="default_onu_vlan_command" value="{{ old('default_onu_vlan_command', $profile->default_onu_vlan_command) }}">
        </div>
        <div>
            <label for="default_onu_mac_command">Default Learned MAC Command</label>
            <input id="default_onu_mac_command" name="default_onu_mac_command" value="{{ old('default_onu_mac_command', $profile->default_onu_mac_command) }}">
        </div>
        <div>
            <label for="vlan_write_context_command">VLAN Write Context Command</label>
            <input id="vlan_write_context_command" name="vlan_write_context_command" value="{{ old('vlan_write_context_command', $profile->vlan_write_context_command) }}" placeholder="interface onu {pon_port}/{onu_id}">
        </div>
        <div>
            <label for="vlan_write_command">VLAN Write Command</label>
            <input id="vlan_write_command" name="vlan_write_command" value="{{ old('vlan_write_command', $profile->vlan_write_command) }}" placeholder="port-vlan {port} mode tag {vlan} pri {priority}">
            <div class="muted">Placeholders: {pon_port}, {onu_id}, {port}, {vlan}, {priority}, {service_port}, {gemport}</div>
        </div>
        <div>
            <label for="save_config_command">Permanent Save Command</label>
            <input id="save_config_command" name="save_config_command" value="{{ old('save_config_command', $profile->save_config_command) }}" placeholder="save">
        </div>
    </div>

    <div class="actions" style="margin-top:16px">
        <button class="btn" type="submit">{{ $submitLabel }}</button>
    </div>
</form>
