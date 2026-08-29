@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Router Users Not In App</h1>
        <div class="muted">
            {{ $unmanagedCount }} router user(s) with no matching party.
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

@if ($unmanagedCount === 0)
    <div class="card"><p class="muted" style="margin:0">Every router PPPoE user is linked to a party. Press <strong>Refresh from routers</strong> to re-check.</p></div>
@else
    <form method="post" action="{{ route('router-users.import') }}" id="router-users-form">
        @csrf
        <div class="card">
            <div class="actions" style="gap:14px;flex-wrap:wrap">
                <label style="font-weight:400;display:flex;gap:6px;align-items:center"><input type="checkbox" name="never_suspend" value="1" style="width:auto"> Special ISP customer</label>
                <label style="font-weight:400;display:flex;gap:6px;align-items:center" title="If a selected router user's name already belongs to a party, overwrite that party with the router's data instead of skipping it. Deleted parties with the same name are always restored."><input type="checkbox" name="update_existing" value="1" style="width:auto"> Update existing party</label>
                <button class="btn" type="submit">Add selected as parties</button>
            </div>
            <p class="muted" style="margin:8px 0 0">Each selected router user becomes a party. <strong>Update existing party</strong> only matters when a name clashes with a party already in the app — tick it to overwrite that party, leave it off to skip and just link the secret.</p>
        </div>

        @foreach ($groups as $routerName => $secrets)
            <section class="card" style="margin-top:14px">
                <h2 style="margin-bottom:8px">{{ $routerName }} <span class="muted">({{ $secrets->count() }})</span></h2>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" data-select-all-router style="width:auto" aria-label="Select all on {{ $routerName }}"></th>
                                <th>Router username</th>
                                <th>Profile</th>
                                <th>Service</th>
                                <th>Remote address</th>
                                <th>Status on router</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($secrets as $secret)
                                <tr>
                                    <td><input type="checkbox" name="secret_ids[]" value="{{ $secret->id }}" class="router-user-check" style="width:auto"></td>
                                    <td>{{ $secret->name }}</td>
                                    <td>{{ $secret->profile ?: '—' }}</td>
                                    <td>{{ $secret->service ?: '—' }}</td>
                                    <td>{{ $secret->remote_address ?: '—' }}</td>
                                    <td>
                                        @if ($secret->router?->read_only)
                                            <span class="badge">Read-only</span>
                                        @else
                                            <span class="badge {{ $secret->disabled ? 'inactive' : 'active' }}">{{ $secret->disabled ? 'disabled' : 'enabled' }}</span>
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
    <script>
        document.querySelectorAll('#router-users-form [data-select-all-router]').forEach(function (master) {
            master.addEventListener('change', function () {
                master.closest('table').querySelectorAll('.router-user-check').forEach(function (cb) { cb.checked = master.checked; });
            });
        });
    </script>
@endif
@endsection
