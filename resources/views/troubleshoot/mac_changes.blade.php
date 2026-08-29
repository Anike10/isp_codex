@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; Frequent MAC Changes</h1>
        <div class="muted">Users who connected from at least <strong>{{ $minMacs }}</strong> different device MAC(s) since {{ $since->format('d/m/Y H:i') }} ({{ $hours }}h window). A swapped router, a shared line, or MAC spoofing all show up here.</div>
    </div>
</div>

@include('troubleshoot._tabs', ['active' => 'mac'])

<form method="get" class="card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
    <div>
        <label class="muted" style="display:block;font-size:12px">Window (hours)</label>
        <input type="number" name="hours" min="1" max="8760" value="{{ $hours }}" style="width:110px">
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">Min distinct MACs</label>
        <input type="number" name="min_macs" min="2" max="100" value="{{ $minMacs }}" style="width:130px">
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">Router</label>
        <select name="router">
            <option value="">All routers</option>
            @foreach ($routers as $r)
                <option value="{{ $r->id }}" @selected($routerId === $r->id)>{{ $r->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn" type="submit">Apply</button>
    <button class="btn light" type="submit" name="make_default" value="1" title="Remember these filters as the default for this page">Make default</button>
    <a class="btn light" href="{{ route('troubleshoot.mac-changes') }}">Reset</a>
    <span class="muted" style="margin-left:auto">{{ $rows->total() }} user(s) over threshold</span>
    @if (request()->query('make_default') === '1')
        <span class="muted" style="flex-basis:100%;color:var(--brand)">Saved &mdash; this page now opens with these filters.</span>
    @endif
</form>

@include('troubleshoot._retention')

<div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Party</th>
                <th class="col-center"># MACs</th>
                <th class="col-center">Events</th>
                <th>Device MACs (newest first)</th>
                <th>Last change</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row->username }}</strong></td>
                    <td>
                        @if ($row->matched_customer)
                            <a href="{{ route('customers.show', $row->matched_customer) }}">{{ $row->matched_customer->name }}</a>
                            <span class="badge {{ $row->matched_customer->status === 'active' ? 'active' : 'inactive' }}">{{ $row->matched_customer->status }}</span>
                        @else
                            <span class="muted">Not in app</span>
                        @endif
                    </td>
                    <td class="col-center"><strong>{{ (int) $row->mac_count }}</strong></td>
                    <td class="col-center">{{ (int) $row->events }}</td>
                    <td>
                        @foreach ($row->recent_macs as $entry)
                            <div style="margin:2px 0">
                                <code>{{ $entry['mac'] }}</code>
                                <span class="muted">&times;{{ $entry['hits'] }} · {{ \Illuminate\Support\Carbon::parse($entry['seen_at'])->format('d/m H:i') }}</span>
                            </div>
                        @endforeach
                    </td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->last_at)->format('d/m/Y H:i:s') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No user connected from that many different MACs in this window.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:14px">{{ $rows->links() }}</div>
@endsection
