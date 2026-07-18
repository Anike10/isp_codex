@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Packages</h1><div class="muted">Internet plans and monthly prices</div></div>
    <a class="btn" href="{{ route('packages.create') }}">Add Package</a>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Package name, speed, MikroTik profile, IP pool, description"></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div><label>Min Price</label><input type="number" step="0.01" name="min_price" value="{{ request('min_price') }}"></div>
    <div><label>Max Price</label><input type="number" step="0.01" name="max_price" value="{{ request('max_price') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('packages.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<form id="package-bulk-delete-form" method="post" action="{{ route('packages.bulk-destroy') }}" class="card bulk-toolbar" style="margin-bottom:16px" onsubmit="return confirm('Delete the selected packages using the selected mode?')">
    @csrf
    @method('DELETE')
    <div class="bulk-toolbar-copy">
        <div class="bulk-toolbar-title"><span class="bulk-toolbar-icon">!</span><strong>Bulk package actions</strong></div>
        <div class="muted">Normal delete assigned package মুছবে না। Force Delete করলে assigned subscriptions replacement package-এ move হবে।</div>
    </div>
    <div class="bulk-toolbar-row">
        <label class="bulk-force-toggle">
            <input id="package-force-delete" type="checkbox" name="force_delete" value="1">
            <span><strong>Force Delete</strong><small>Move subscriptions safely</small></span>
        </label>
        <div id="package-replacement-wrap" class="bulk-replacement" style="display:none">
            <label>Users will receive</label>
            <select id="package-replacement" name="replacement_package_id" disabled>
                <option value="">Select replacement package</option>
                @foreach ($replacementPackages as $replacementPackage)
                    <option value="{{ $replacementPackage->id }}">{{ $replacementPackage->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn danger" type="submit">Delete Selected</button>
    </div>
</form>

<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="muted" style="margin:0 0 8px">নাম, স্পিড, MikroTik Profile, মাসিক মূল্য বা Status-এ double-click করে সরাসরি edit করুন। Enter/বাইরে click করলে save, Esc চাপলে বাতিল।</div>
<table>
    <thead><tr><th><input id="package-select-all" type="checkbox" style="width:auto" aria-label="Select all packages on this page"></th><th>SL</th><th>Name</th><th>Speed</th><th>MikroTik Profile</th><th>IP Pool</th><th>Monthly Price</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    @forelse ($packages as $packageIndex => $package)
        <tr>
            <td><input class="package-select" type="checkbox" name="package_ids[]" value="{{ $package->id }}" form="package-bulk-delete-form" style="width:auto" aria-label="Select {{ $package->name }}"></td>
            <td>{{ ($packages->firstItem() ?? 1) + $packageIndex }}</td>
            <td data-inline-field="name" data-inline-url="{{ route('packages.inline-update', $package) }}"><span data-inline-value>{{ $package->name }}</span></td>
            <td data-inline-field="speed" data-inline-url="{{ route('packages.inline-update', $package) }}"><span data-inline-value>{{ $package->speed }}</span></td>
            <td data-inline-field="mikrotik_profile" data-inline-url="{{ route('packages.inline-update', $package) }}"><span data-inline-value>{{ $package->mikrotik_profile }}</span></td>
            <td>
                @if ($package->default_ip_pool)
                    <span class="badge active">{{ $package->default_ip_pool }}</span>
                @else
                    <span class="muted">RouterOS default / none</span>
                @endif
            </td>
            <td data-inline-field="monthly_price" data-inline-url="{{ route('packages.inline-update', $package) }}" data-input-type="number"><span data-inline-value>{{ number_format($package->monthly_price, 2) }}</span></td>
            <td data-inline-field="status" data-inline-url="{{ route('packages.inline-update', $package) }}" data-input-type="status"><span class="badge {{ $package->status }}" data-inline-value>{{ $package->status }}</span></td>
            <td>
                <div class="actions">
                    <a class="btn light" href="{{ route('packages.show', $package) }}">View</a>
                    <a class="btn secondary" href="{{ route('packages.edit', $package) }}">Edit</a>
                    <form method="post" action="{{ route('packages.destroy', $package) }}" onsubmit="return confirm('Delete package {{ addslashes($package->name) }}? Packages assigned to customers cannot be deleted.')">
                        @csrf
                        @method('DELETE')
                        <button class="btn danger" type="submit">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="9">No packages found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $packages->links() }}</div>

<script>
const packageCsrfToken = document.querySelector('meta[name="csrf-token"]').content;
const packageSelectAll = document.querySelector('#package-select-all');
const packageSelections = Array.from(document.querySelectorAll('.package-select'));
const packageForceDelete = document.querySelector('#package-force-delete');
const packageReplacementWrap = document.querySelector('#package-replacement-wrap');
const packageReplacement = document.querySelector('#package-replacement');

packageSelectAll?.addEventListener('change', () => {
    packageSelections.forEach(checkbox => checkbox.checked = packageSelectAll.checked);
});

packageForceDelete?.addEventListener('change', () => {
    packageReplacementWrap.style.display = packageForceDelete.checked ? 'block' : 'none';
    packageReplacement.disabled = ! packageForceDelete.checked;
    packageReplacement.required = packageForceDelete.checked;
});

function editPackageCell(cell) {
    if (cell.querySelector('input, select')) return;

    const valueNode = cell.querySelector('[data-inline-value]');
    const originalValue = valueNode.textContent.trim();
    const field = cell.dataset.inlineField;
    const input = cell.dataset.inputType === 'status' ? document.createElement('select') : document.createElement('input');

    if (input.tagName === 'SELECT') {
        ['active', 'inactive'].forEach(value => {
            const option = new Option(value, value, false, originalValue === value);
            input.add(option);
        });
    } else {
        input.type = cell.dataset.inputType || 'text';
        input.value = field === 'monthly_price' ? originalValue.replace(/,/g, '') : originalValue;
        if (input.type === 'number') input.step = '0.01';
    }

    input.style.width = '100%';
    cell.replaceChildren(input);
    input.focus();
    if (input.select) input.select();

    let saving = false;
    const cancel = () => {
        if (saving) return;
        cell.replaceChildren(valueNode);
    };
    const save = async () => {
        if (saving) return;
        saving = true;
        input.disabled = true;
        try {
            const response = await fetch(cell.dataset.inlineUrl, {
                method: 'PATCH',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': packageCsrfToken},
                body: JSON.stringify({field, value: input.value}),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not save package');
            valueNode.textContent = data.value;
            if (field === 'status') valueNode.className = `badge ${data.status}`;
            cell.replaceChildren(valueNode);
        } catch (error) {
            alert(error.message);
            cell.replaceChildren(valueNode);
        }
    };
    input.addEventListener('blur', save);
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); save(); }
        if (event.key === 'Escape') { event.preventDefault(); cancel(); }
    });
}

document.querySelectorAll('[data-inline-field]').forEach(cell => cell.addEventListener('dblclick', event => {
    event.preventDefault();
    editPackageCell(cell);
}));
</script>
@endsection
