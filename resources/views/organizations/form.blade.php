@extends('layouts.app')
@section('content')
<div class="topbar">
    <h1>{{ $organization->exists ? 'Edit Organization' : 'Add Organization' }}</h1>
    <div class="actions">
        @if ($organization->exists)
            <a class="btn light" href="#please-call-page-settings">Edit Please Call Page</a>
            <a class="btn" href="{{ route('service-inactive') }}" target="_blank" rel="noopener">Preview Please Call Page</a>
        @endif
        <a class="btn light" href="{{ route('organizations.index') }}">Back</a>
    </div>
</div>
<form method="post" action="{{ $organization->exists ? route('organizations.update', $organization) : route('organizations.store') }}" class="card form-grid">@csrf @if($organization->exists) @method('put') @endif
<div><label>Name</label><input name="name" value="{{ old('name', $organization->name) }}" required></div>
<div><label>Mobile</label><input name="mobile" value="{{ old('mobile', $organization->mobile) }}"></div>
<div><label>Phone / Landline</label><input name="phone" value="{{ old('phone', $organization->phone) }}"></div>
<div class="full" id="please-call-page-settings" style="scroll-margin-top:20px">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-top:8px">
        <h2 style="margin:0">Please Call Page Settings</h2>
        <a class="btn light" href="{{ route('service-inactive') }}" target="_blank" rel="noopener">Preview Page</a>
    </div>
</div>
<div class="full muted">Inactive PPPoE customers are sent to the default organization's public page. Edit the page content below, then save before opening the preview. Enter one phone number per line; every number appears as a tap-to-call button.</div>
<div>
    <label>Organization / Brand Name</label>
    <input name="please_call_brand_name" value="{{ old('please_call_brand_name', $organization->please_call_brand_name) }}" placeholder="{{ $organization->name ?: 'Ultimate Solution' }}">
    @error('please_call_brand_name')<div class="error" style="margin-top:6px">{{ $message }}</div>@enderror
</div>
<div>
    <label>Page Heading</label>
    <input name="please_call_title" value="{{ old('please_call_title', $organization->please_call_title) }}" placeholder="Please Call">
    @error('please_call_title')<div class="error" style="margin-top:6px">{{ $message }}</div>@enderror
</div>
<div class="full">
    <label>Footer / Help Text</label>
    <input name="please_call_footer" value="{{ old('please_call_footer', $organization->please_call_footer) }}" placeholder="Call for billing or connection support">
    @error('please_call_footer')<div class="error" style="margin-top:6px">{{ $message }}</div>@enderror
</div>
<div class="full">
    <label>Page Message</label>
    <textarea name="please_call_message" rows="3" placeholder="Your internet connection is currently inactive. Please call one of the numbers below to restore service.">{{ old('please_call_message', $organization->please_call_message) }}</textarea>
    @error('please_call_message')<div class="error" style="margin-top:6px">{{ $message }}</div>@enderror
</div>
<div class="full">
    <label>Phone Numbers</label>
    <textarea name="please_call_numbers" rows="5" placeholder="01700 000000&#10;01800 000000">{{ old('please_call_numbers', $organization->please_call_numbers) }}</textarea>
    @error('please_call_numbers')<div class="error" style="margin-top:6px">{{ $message }}</div>@enderror
    <div class="muted" style="margin-top:6px">Public page: <a href="{{ route('service-inactive') }}" target="_blank" rel="noopener">{{ route('service-inactive') }}</a></div>
    <div class="muted" style="margin-top:6px">HTTP requests and device captive-portal checks can open this page automatically. Third-party HTTPS pages cannot be transparently replaced without a browser certificate error, so they are blocked instead.</div>
</div>
<div><label>Email</label><input type="email" name="email" value="{{ old('email', $organization->email) }}"></div>
<div><label>Website</label><input name="website" value="{{ old('website', $organization->website) }}"></div>
<div><label>Tax / BIN ID</label><input name="tax_id" value="{{ old('tax_id', $organization->tax_id) }}"></div>
<div class="full"><label>Logo URL</label><input name="logo_url" value="{{ old('logo_url', $organization->logo_url) }}" placeholder="/images/logo.png or https://..."></div>
<div class="full"><label>Address</label><textarea name="address">{{ old('address', $organization->address) }}</textarea></div>
<div class="full"><label>Print Footer Note</label><textarea name="footer_note">{{ old('footer_note', $organization->footer_note) }}</textarea></div>
<div class="full"><label>Default Payment Note</label><textarea name="payment_note" rows="6">{{ old('payment_note', $defaultPaymentNote ?? '') }}</textarea></div>
<div class="full"><h2 style="margin-top:8px">Print Preferences</h2></div>
<div class="full muted">The Organization selector is always available on every print page. These settings control the other options that start selected.</div>
<div><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="default_without_signature" value="1" style="width:auto" @checked(old('default_without_signature', $organization->default_without_signature))> Default: Print without signature</label></div>
<div class="full"><h2 style="margin-top:8px">Bank Account Information</h2></div>
<div><label>Bank Name</label><input name="bank_name" value="{{ old('bank_name', $organization->bank_name) }}"></div>
<div><label>Account Name</label><input name="bank_account_name" value="{{ old('bank_account_name', $organization->bank_account_name) }}"></div>
<div><label>Account Number</label><input name="bank_account_number" value="{{ old('bank_account_number', $organization->bank_account_number) }}"></div>
<div><label>Branch</label><input name="bank_branch" value="{{ old('bank_branch', $organization->bank_branch) }}"></div>
<div><label>Routing Number</label><input name="bank_routing_number" value="{{ old('bank_routing_number', $organization->bank_routing_number) }}"></div>
<div><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="show_bank_info_on_invoice" value="1" style="width:auto" @checked(old('show_bank_info_on_invoice', $organization->show_bank_info_on_invoice))> Default: Show bank information on Invoice print</label><span class="muted">Requires an Account Number. This option can be changed again on the Invoice print page.</span></div>
<div><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_default" value="1" style="width:auto" @checked(old('is_default', $organization->is_default))> Default organization</label>@error('is_default')<div class="error" style="margin-top:6px">{{ $message }}</div>@enderror</div>
<div><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $organization->exists ? $organization->is_active : true))> Active for printing</label></div>
<div class="full"><h2 style="margin-top:8px">Overdue Auto-Disable Schedule</h2></div>
<div class="full muted">Expired or overdue parties (except Special ISP) are set inactive by an hourly background job. It runs only inside this daily window &mdash; so parties are never cut off at night and support calls stay in office hours. Payments still reactivate a party instantly, any time.</div>
@php
    $bwStart = (int) old('billing_disable_start_hour', $billingWindow['start']);
    $bwEnd = (int) old('billing_disable_end_hour', $billingWindow['end']);
    $bwHours = $bwEnd >= $bwStart ? range($bwStart, $bwEnd) : [$bwStart];
    $bwSkipDays = old('billing_disable_skip_days_present') !== null
        ? old('billing_disable_skip_days', [])
        : $billingSkipDays;
    $bwSkipDays = array_map('intval', (array) $bwSkipDays);
@endphp
<div>
    <label>Window start hour</label>
    <select name="billing_disable_start_hour" id="billing_disable_start_hour">
        @for ($h = 0; $h < 24; $h++)
            <option value="{{ $h }}" @selected($bwStart === $h)>{{ sprintf('%02d:00', $h) }}</option>
        @endfor
    </select>
</div>
<div>
    <label>Window end hour</label>
    <select name="billing_disable_end_hour" id="billing_disable_end_hour">
        @for ($h = 0; $h < 24; $h++)
            <option value="{{ $h }}" @selected($bwEnd === $h)>{{ sprintf('%02d:00', $h) }}</option>
        @endfor
    </select>
</div>
<div class="full">
    <label>Do not auto-disable on</label>
    <input type="hidden" name="billing_disable_skip_days_present" value="1">
    <div style="display:flex;flex-wrap:wrap;gap:10px 18px;margin-top:8px">
        @foreach ($billingDayOptions as $dayNumber => $dayLabel)
            <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400">
                <input type="checkbox" name="billing_disable_skip_days[]" value="{{ $dayNumber }}" style="width:auto" @checked(in_array($dayNumber, $bwSkipDays, true))>
                {{ $dayLabel }}
            </label>
        @endforeach
    </div>
    <span class="muted">On selected days, no expired or overdue party will be newly set inactive. For example, select Friday to keep the auto-disable job off for all of Friday.</span>
</div>
<div class="full muted" id="billing-window-hint" data-tpl="The job fires on the hour at every step from start to end (%START% &rarr; %END% runs the check at %LIST%).">
    The job fires on the hour at every step from start to end ({{ sprintf('%02d:00', $bwStart) }} &rarr; {{ sprintf('%02d:00', $bwEnd) }} runs the check at {{ implode(', ', $bwHours) }}).
</div>
<script>
    (function () {
        var startSel = document.getElementById('billing_disable_start_hour');
        var endSel = document.getElementById('billing_disable_end_hour');
        var hint = document.getElementById('billing-window-hint');
        if (!startSel || !endSel || !hint) return;
        var pad = function (n) { return (n < 10 ? '0' : '') + n + ':00'; };
        var update = function () {
            var s = parseInt(startSel.value, 10);
            var e = parseInt(endSel.value, 10);
            var list = [];
            if (e >= s) { for (var h = s; h <= e; h++) list.push(h); } else { list.push(s); }
            hint.textContent = hint.dataset.tpl
                .replace('%START%', pad(s))
                .replace('%END%', pad(e))
                .replace('%LIST%', list.join(', '));
        };
        startSel.addEventListener('change', update);
        endSel.addEventListener('change', update);
    })();
</script>
<div class="full actions">
    <button class="btn light" type="submit">Save Organization</button>
    <button class="btn" type="submit" name="apply_inactive_portal" value="1" onclick="return confirm('Save these settings and apply the Please Call redirect to every active writable MikroTik router?')">Save &amp; Apply to All MikroTik</button>
</div></form>
@endsection
