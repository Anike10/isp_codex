@extends('layouts.app')
@section('content')
<div class="topbar"><h1>{{ $organization->exists ? 'Edit Organization' : 'Add Organization' }}</h1><a class="btn light" href="{{ route('organizations.index') }}">Back</a></div>
<form method="post" action="{{ $organization->exists ? route('organizations.update', $organization) : route('organizations.store') }}" class="card form-grid">@csrf @if($organization->exists) @method('put') @endif
<div><label>Name</label><input name="name" value="{{ old('name', $organization->name) }}" required></div>
<div><label>Mobile</label><input name="mobile" value="{{ old('mobile', $organization->mobile) }}"></div>
<div><label>Phone / Landline</label><input name="phone" value="{{ old('phone', $organization->phone) }}"></div>
<div><label>Email</label><input type="email" name="email" value="{{ old('email', $organization->email) }}"></div>
<div><label>Website</label><input name="website" value="{{ old('website', $organization->website) }}"></div>
<div><label>Tax / BIN ID</label><input name="tax_id" value="{{ old('tax_id', $organization->tax_id) }}"></div>
<div class="full"><label>Logo URL</label><input name="logo_url" value="{{ old('logo_url', $organization->logo_url) }}" placeholder="/images/logo.png or https://..."></div>
<div class="full"><label>Address</label><textarea name="address">{{ old('address', $organization->address) }}</textarea></div>
<div class="full"><label>Print Footer Note</label><textarea name="footer_note">{{ old('footer_note', $organization->footer_note) }}</textarea></div>
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
<div><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_default" value="1" style="width:auto" @checked(old('is_default', $organization->is_default))> Default organization</label></div>
<div><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $organization->exists ? $organization->is_active : true))> Active for printing</label></div>
<div class="full"><button class="btn" type="submit">Save Organization</button></div></form>
@endsection
