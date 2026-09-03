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
        <h1>Troubleshoot &mdash; Data Usage Summary</h1>
        <div class="muted">Every party's completed-session download &amp; upload for the selected window, captured from RouterOS events. Click a column to sort.</div>
    </div>
</div>

<form method="get" class="card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <div>
        <label class="muted" style="display:block;font-size:12px">Username contains</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="pppoe-100" style="width:200px">
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">MikroTik</label>
        <select name="router_id">
            <option value="">All routers</option>
            @foreach ($routers as $router)
                <option value="{{ $router->id }}" @selected($routerId === $router->id)>{{ $router->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">Window</label>
        <select name="days" @disabled($rangeActive)>
            @foreach ([1 => 'Last 24 hours', 7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last year'] as $val => $lbl)
                <option value="{{ $val }}" @selected(! $rangeActive && $days === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">From date</label>
        <input type="date" name="from" value="{{ $from?->toDateString() }}">
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">To date</label>
        <input type="date" name="to" value="{{ $to?->toDateString() }}">
    </div>
    <button class="btn" type="submit">Apply</button>
    <button class="btn light" type="submit" name="make_default" value="1" title="Remember this sort &amp; these filters as the default for this page">Make default</button>
    <a class="btn light" href="{{ route('troubleshoot.data-usage') }}">Reset</a>
    <span class="muted" style="margin-left:auto">
        {{ $rows->total() }} user(s)
        @if ($rangeActive)
            {{ $since->format('d/m/Y') }} &ndash; {{ $until->format('d/m/Y') }}
        @else
            since {{ $since->format('d/m/Y H:i') }}
        @endif
    </span>
    <span class="muted" style="flex-basis:100%;font-size:12px">Set a From/To date to report on an exact range; leave both empty to use the rolling window.</span>
    @if (request()->query('make_default') === '1')
        <span class="muted" style="flex-basis:100%;color:var(--brand)">Saved &mdash; this page now opens with this sort &amp; these filters.</span>
    @endif
</form>

@include('troubleshoot._retention')

<div class="table-scroll">
    <table>
        <thead>
            <tr>
                @php([$u, $ua] = $sortLink('username'))
                @php([$rt, $rta] = $sortLink('router_name'))
                @php([$se, $sea] = $sortLink('sessions'))
                @php([$dl, $dla] = $sortLink('download_bytes'))
                @php([$ul, $ula] = $sortLink('upload_bytes'))
                @php([$to, $toa] = $sortLink('total_bytes'))
                @php([$la, $laa] = $sortLink('last_at'))
                <th><a href="{{ $u }}">Username{{ $ua }}</a></th>
                <th>Party</th>
                <th><a href="{{ $rt }}">MikroTik{{ $rta }}</a></th>
                <th class="col-center"><a href="{{ $se }}">Sessions{{ $sea }}</a></th>
                <th class="col-center"><a href="{{ $dl }}">Download{{ $dla }}</a></th>
                <th class="col-center"><a href="{{ $ul }}">Upload{{ $ula }}</a></th>
                <th class="col-center"><a href="{{ $to }}">Total{{ $toa }}</a></th>
                <th><a href="{{ $la }}">Last session{{ $laa }}</a></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row->username }}</strong></td>
                    <td>
                        @if ($row->matched_customer)
                            <a href="{{ route('troubleshoot.data-usage.show', $row->matched_customer) }}">{{ $row->matched_customer->name }}</a>
                        @else
                            <span class="muted">Not in app</span>
                        @endif
                    </td>
                    <td>{{ $row->router_name ?: '—' }}</td>
                    <td class="col-center">{{ (int) $row->sessions }}</td>
                    <td class="col-center">@include('troubleshoot._bytes', ['bytes' => $row->download_bytes])</td>
                    <td class="col-center">@include('troubleshoot._bytes', ['bytes' => $row->upload_bytes])</td>
                    <td class="col-center"><strong>@include('troubleshoot._bytes', ['bytes' => $row->total_bytes])</strong></td>
                    <td>{{ $row->last_at ? \Illuminate\Support\Carbon::parse($row->last_at)->format('d/m/Y H:i:s') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No usage logged in this window.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:14px">{{ $rows->links() }}</div>
@endsection
