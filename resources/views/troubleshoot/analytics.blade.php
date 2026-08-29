@extends('layouts.app')

@php
    $sortLink = function (string $col) use ($sort, $dir) {
        $nextDir = ($sort === $col && $dir === 'desc') ? 'asc' : 'desc';
        $arrow = $sort === $col ? ($dir === 'desc' ? ' ▼' : ' ▲') : '';

        return [request()->fullUrlWithQuery(['sort' => $col, 'dir' => $nextDir]), $arrow];
    };
@endphp

@section('content')
<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; Connection Analytics</h1>
        <div class="muted">Every user seen in the disconnect log, with counts across 24h / 7d / 30d / all time. Click a column to sort.</div>
    </div>
</div>

@include('troubleshoot._tabs', ['active' => 'analytics'])

<form method="get" class="card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <div>
        <label class="muted" style="display:block;font-size:12px">Username contains</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="pppoe-100" style="width:200px">
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
    <a class="btn light" href="{{ route('troubleshoot.analytics') }}">Reset</a>
    <span class="muted" style="margin-left:auto">{{ $rows->total() }} user(s)</span>
</form>

<div class="table-scroll">
    <table>
        <thead>
            <tr>
                @php([$u, $ua] = $sortLink('username'))
                @php([$s1, $s1a] = $sortLink('d24h'))
                @php([$s7, $s7a] = $sortLink('d7d'))
                @php([$s30, $s30a] = $sortLink('d30d'))
                @php([$sall, $salla] = $sortLink('dall'))
                @php([$sl, $sla] = $sortLink('last_at'))
                <th><a href="{{ $u }}">Username{{ $ua }}</a></th>
                <th>Party</th>
                <th class="col-center"><a href="{{ $s1 }}">Last 24h{{ $s1a }}</a></th>
                <th class="col-center"><a href="{{ $s7 }}">Last 7d{{ $s7a }}</a></th>
                <th class="col-center"><a href="{{ $s30 }}">Last 30d{{ $s30a }}</a></th>
                <th class="col-center"><a href="{{ $sall }}">All time{{ $salla }}</a></th>
                <th class="col-center">ONU Rx power</th>
                <th><a href="{{ $sl }}">Last disconnect{{ $sla }}</a></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row->username }}</strong></td>
                    <td>
                        @if ($row->matched_customer)
                            <a href="{{ route('customers.show', $row->matched_customer) }}">{{ $row->matched_customer->name }}</a>
                        @else
                            <span class="muted">Not in app</span>
                        @endif
                    </td>
                    <td class="col-center">{{ (int) $row->d24h }}</td>
                    <td class="col-center">{{ (int) $row->d7d }}</td>
                    <td class="col-center">{{ (int) $row->d30d }}</td>
                    <td class="col-center"><strong>{{ (int) $row->dall }}</strong></td>
                    <td class="col-center">@include('troubleshoot._rx_power', ['row' => $row])</td>
                    <td>{{ $row->last_at ? \Illuminate\Support\Carbon::parse($row->last_at)->format('d/m/Y H:i:s') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No disconnect events logged yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:14px">{{ $rows->links() }}</div>
@endsection
