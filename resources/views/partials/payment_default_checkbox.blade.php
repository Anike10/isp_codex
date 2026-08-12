<label style="display:inline-flex;align-items:center;gap:6px;margin:0;font-size:13px;font-weight:600;cursor:pointer">
    <input
        type="checkbox"
        name="set_as_default"
        value="1"
        @isset($paymentDefaultForm) form="{{ $paymentDefaultForm }}" @endisset
        @checked(old('set_as_default') === '1')
        style="width:16px;height:16px;margin:0;accent-color:var(--accent)"
    >
    <span>Set as default</span>
</label>
