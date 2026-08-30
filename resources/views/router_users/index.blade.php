@extends('layouts.app')

@php
    $selectedRouterName = $selectedRouterId ? optional($routers->firstWhere('id', $selectedRouterId))->name : null;
@endphp

@section('content')
<div class="topbar">
    <div>
        <h1>Router Users</h1>
        <div class="muted">
            {{ $totalCount }} imported router user(s){{ $selectedRouterName ? ' on '.$selectedRouterName : '' }} —
            <strong>{{ $matchedCount }}</strong> linked to a party,
            <strong>{{ $unmanagedCount }}</strong> with no matching party.
            Last checked: {{ $lastCheckedAt ? \Illuminate\Support\Carbon::parse($lastCheckedAt)->diffForHumans() : 'never' }}
        </div>
    </div>
    <div class="actions" style="gap:8px;flex-wrap:wrap">
        <form method="post" action="{{ route('router-users.refresh') }}">
            @csrf
            <button class="btn light" type="submit">Refresh secrets</button>
        </form>
        <form method="post" action="{{ route('router-users.refresh-active') }}" style="display:flex;gap:6px" title="Pull /ppp/active connections from every active router. Connected users without a real /ppp/secret are stored with this shared password so you can add them as parties below.">
            @csrf
            <input type="text" name="active_password" required placeholder="Shared password" autocomplete="off" style="width:150px">
            <button class="btn light" type="submit">Pull active connections</button>
        </form>
        <a class="btn light" href="{{ route('dashboard') }}">Back to Dashboard</a>
    </div>
</div>
@error('active_password')<div class="card" style="border-color:#c0392b"><p class="muted" style="margin:0;color:#c0392b">{{ $message }}</p></div>@enderror

<form method="get" class="card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <label class="muted" for="router-filter" style="font-weight:600">Router</label>
    <select id="router-filter" name="router" onchange="this.form.submit()">
        <option value="">All routers</option>
        @foreach ($routers as $r)
            <option value="{{ $r->id }}" @selected($selectedRouterId === $r->id)>{{ $r->name }}</option>
        @endforeach
    </select>
    <noscript><button class="btn light" type="submit">Filter</button></noscript>
    @if ($selectedRouterId)
        <a class="btn light" href="{{ route('router-users.index') }}">Clear</a>
    @endif
</form>

@if ($totalCount === 0)
    <div class="card"><p class="muted" style="margin:0">No router users imported{{ $selectedRouterName ? ' for '.$selectedRouterName : '' }} yet. Press <strong>Refresh secrets</strong> or <strong>Pull active connections</strong> to load them.</p></div>
@else
    <form method="post" action="{{ route('router-users.import') }}" id="router-users-form">
        @csrf
        @if ($unmanagedCount > 0)
            <div class="card">
                <div class="actions" style="gap:14px;flex-wrap:wrap">
                    <label style="font-weight:400;display:flex;gap:6px;align-items:center"><input type="checkbox" name="never_suspend" value="1" style="width:auto"> Special ISP customer</label>
                    <label style="font-weight:400;display:flex;gap:6px;align-items:center" title="If a selected router user's name already belongs to a party, overwrite that party with the router's data instead of skipping it. Deleted parties with the same name are always restored."><input type="checkbox" name="update_existing" value="1" style="width:auto"> Update existing party</label>
                    <button class="btn" type="submit">Add selected as parties</button>
                </div>
                <p class="muted" style="margin:8px 0 0">Only router users <strong>with no matching party</strong> can be selected. <strong>Update existing party</strong> only matters when a name clashes with a party already in the app — tick it to overwrite that party, leave it off to skip and just link the secret.</p>
            </div>
        @endif

        @foreach ($groups as $routerName => $secrets)
            @php
                $groupUnmatched = $secrets->where('is_unmanaged', true)->count();
                $groupMatched = $secrets->count() - $groupUnmatched;
            @endphp
            <section class="card" style="margin-top:14px">
                <h2 style="margin-bottom:8px">{{ $routerName }}
                    <span class="muted">({{ $secrets->count() }} — {{ $groupMatched }} linked, {{ $groupUnmatched }} not in app)</span>
                </h2>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" data-select-all-router style="width:auto" aria-label="Select all unmatched on {{ $routerName }}"></th>
                                <th>Router username</th>
                                <th>Party</th>
                                <th>Device MAC</th>
                                <th>Profile</th>
                                <th>Service</th>
                                <th>Remote address</th>
                                <th>Status at last refresh</th>
                                <th>Cleanup</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($secrets as $secret)
                                <tr>
                                    <td>
                                        @if ($secret->is_unmanaged)
                                            <input type="checkbox" name="secret_ids[]" value="{{ $secret->id }}" class="router-user-check" style="width:auto">
                                        @endif
                                    </td>
                                    <td>{{ $secret->name }}</td>
                                    <td>
                                        @if ($secret->matched_customer)
                                            <span class="badge active">{{ $secret->customer_id ? '✓ Linked' : '✓ Name match' }}</span>
                                            <a href="{{ route('customers.show', $secret->matched_customer) }}">{{ $secret->matched_customer->name }}</a>
                                        @else
                                            <span class="badge overdue">Not in app</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php($deviceMac = $secret->device_mac ?: $secret->matched_customer?->last_connected_mac)
                                        @if ($deviceMac)
                                            <code>{{ $deviceMac }}</code>
                                            @if ($secret->device_mac)
                                                <div class="muted">from last /ppp/active poll</div>
                                            @elseif ($secret->matched_customer?->last_connected_at)
                                                <div class="muted">party, {{ $secret->matched_customer->last_connected_at->diffForHumans() }}</div>
                                            @endif
                                        @else
                                            <span class="muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $secret->profile ?: '—' }}</td>
                                    <td>{{ $secret->service ?: '—' }}</td>
                                    <td>{{ $secret->remote_address ?: '—' }}</td>
                                    <td>
                                        @if ($secret->isActiveSessionOnly())
                                            <span class="badge active">active session</span>
                                        @else
                                            <span class="badge {{ $secret->disabled ? 'inactive' : 'active' }}">{{ $secret->disabled ? 'disabled' : 'enabled' }}</span>
                                        @endif
                                        @if ($secret->router?->read_only)
                                            <div class="muted">read-only router</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($secret->is_unmanaged)
                                            @if ($secret->router?->read_only)
                                                <span class="muted" title="This router is read-only — remove the secret on the MikroTik itself, then Refresh secrets.">remove on router</span>
                                            @else
                                                <button type="submit" class="btn danger" form="forget-secret-{{ $secret->id }}"
                                                    onclick="return confirm('Delete router user &quot;{{ $secret->name }}&quot; from {{ $routerName }}? This removes the PPPoE secret on the MikroTik and drops any live session.')">
                                                    Delete from MikroTik
                                                </button>
                                            @endif
                                        @else
                                            <span class="muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </form>

    {{-- Per-row "Delete from MikroTik" posts live outside the import form: a
         <form> cannot be nested inside another, so each button targets one of
         these by id via its form="" attribute. --}}
    @foreach ($groups as $secrets)
        @foreach ($secrets as $secret)
            @if ($secret->is_unmanaged && ! $secret->router?->read_only)
                <form id="forget-secret-{{ $secret->id }}" method="post" action="{{ route('router-users.destroy-secret', $secret) }}" hidden>
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        @endforeach
    @endforeach
    <script>
        document.querySelectorAll('#router-users-form [data-select-all-router]').forEach(function (master) {
            master.addEventListener('change', function () {
                master.closest('table').querySelectorAll('.router-user-check').forEach(function (cb) { cb.checked = master.checked; });
            });
        });
    </script>
@endif
@endsection
