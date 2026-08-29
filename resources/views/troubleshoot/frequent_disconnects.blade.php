@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; Frequent Disconnects</h1>
        <div class="muted">Users whose PPP session dropped at least <strong>{{ $minCount }}</strong> time(s) since {{ $since->format('d/m/Y H:i') }} ({{ $hours }}h window).</div>
    </div>
</div>

@include('troubleshoot._tabs', ['active' => 'frequent'])

<form method="get" class="card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
    <div>
        <label class="muted" style="display:block;font-size:12px">Window (hours)</label>
        <input type="number" name="hours" min="1" max="8760" value="{{ $hours }}" style="width:110px">
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">Min disconnects</label>
        <input type="number" name="min_count" min="1" max="10000" value="{{ $minCount }}" style="width:120px">
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
    <a class="btn light" href="{{ route('troubleshoot.frequent-disconnects') }}">Reset</a>
    <span class="muted" style="margin-left:auto">{{ $rows->total() }} user(s) over threshold</span>
</form>

<div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Party</th>
                <th class="col-center">Disconnects</th>
                <th class="col-center">Routers</th>
                <th class="col-center">ONU Rx power</th>
                <th>Last disconnect</th>
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
                    <td class="col-center"><strong>{{ (int) $row->disconnects }}</strong></td>
                    <td class="col-center">{{ (int) $row->routers }}</td>
                    <td class="col-center">@include('troubleshoot._rx_power', ['row' => $row])</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->last_at)->format('d/m/Y H:i:s') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No user crossed the threshold in this window.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:14px">{{ $rows->links() }}</div>
@endsection
