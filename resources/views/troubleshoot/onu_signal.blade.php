@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; ONU Signal History</h1>
        <div class="muted">
            Stored ONU Rx/Tx optical power, sampled every {{ $intervalHours }}h.
            Showing <strong>{{ $from->format('d/m/Y') }}</strong> to <strong>{{ $to->format('d/m/Y') }}</strong>.
            The green band is −15 to −25 dBm (normal). Samples older than {{ $retentionDays }} day(s) are deleted (retention is set on the OLT / ONU page).
        </div>
    </div>
</div>

@include('troubleshoot._tabs', ['active' => 'onu-signal'])

<style>
    .onu-allsig__bar {
        position: sticky; top: 0; z-index: 6;
        display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        padding: 10px 12px; margin-bottom: 14px;
        background: var(--card, #fff); border: 1px solid var(--line, #e6ebf2); border-radius: 10px;
        font-size: 13px;
    }
    .onu-signal__show { display: inline-flex; align-items: center; gap: 10px; margin: 0; }
    .onu-signal__show label { display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
    .onu-allsig__bar .per-page-form { margin: 0; }
    /* No box / top rule around each party block — only the graph line stands out. */
    .onu-allsig__card { margin: 22px 0 0; padding: 0; border: 0; background: transparent; box-shadow: none; }
    .onu-allsig__card h2 { margin: 0 0 6px; font-size: 15px; }
    .onu-allsig__card h2 .muted { font-weight: 400; font-size: 12px; }
    .onu-allsig__card h2 .badge.unstable { background: #fdecec; color: #b42318; border: 1px solid #f4bcbc; }
</style>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full">
        <label>Party name / username</label>
        <input name="q" value="{{ $search }}" placeholder="Name, connection ID, MikroTik username, phone or MAC">
    </div>
    <div>
        <label>Date from</label>
        <input type="date" name="from" value="{{ $from->toDateString() }}">
    </div>
    <div>
        <label>Date to</label>
        <input type="date" name="to" value="{{ $to->toDateString() }}">
    </div>
    <div>
        <label>Stability</label>
        <select name="stability">
            <option value="">All parties</option>
            <option value="unstable" @selected($unstableOnly)>Not stable only ({{ $unstableCount }})</option>
        </select>
    </div>
    <div>
        <label>Swing threshold (dB)</label>
        <input type="number" name="swing" min="0" max="40" step="0.5" value="{{ $swing + 0 }}">
    </div>
    <div class="full actions">
        <button class="btn secondary" type="submit">Search</button>
        <a class="btn light" href="{{ route('troubleshoot.onu-signal') }}">Reset</a>
    </div>
</form>

<div class="onu-allsig__bar">
    <span class="muted">Showing {{ $pagination?->firstItem() ?? 0 }}–{{ $pagination?->lastItem() ?? 0 }} of {{ $pagination?->total() ?? 0 }} parties@if ($unstableOnly) · not stable@endif</span>
    <form method="post" action="{{ route('onu-signal.visibility.update') }}" class="onu-signal__show">
        @csrf
        @method('patch')
        <span class="muted">Show on every graph:</span>
        <label><input type="checkbox" name="show_rx" value="1" @checked($showRx)> Rx</label>
        <label><input type="checkbox" name="show_tx" value="1" @checked($showTx)> Tx</label>
        <noscript><button class="btn light" type="submit">Save</button></noscript>
    </form>
    @include('partials.per_page')
</div>

@forelse ($parties as $party)
    <section class="onu-allsig__card">
        <h2>
            <a href="{{ route('customers.show', $party) }}">{{ $party->name }}</a>
            <span class="muted">· {{ $party->connection_id ?? ('#'.$party->id) }}@if ($party->last_connected_mac) · {{ $party->last_connected_mac }}@endif</span>
            @if ($party->onu_unstable ?? false)<span class="badge unstable">not stable</span>@endif
        </h2>
        @include('customers._onu_signal_chart', [
            'samples' => $party->onuSamplesWindow,
            'showRx' => $showRx,
            'showTx' => $showTx,
            'showLegend' => true,
        ])
    </section>
@empty
    <div class="card"><p class="muted" style="margin:0">
        @if ($search !== '' || $unstableOnly)
            এই ফিল্টারে কোনো পার্টির ONU signal নমুনা পাওয়া যায়নি। তারিখ বা সার্চ পরিবর্তন করে দেখুন।
        @else
            এখনো কোনো পার্টির ONU signal নমুনা জমা হয়নি। সংগ্রহ শুরু হলে (প্রতি {{ $intervalHours }}h) এখানে সব পার্টির গ্রাফ দেখা যাবে।
        @endif
    </p></div>
@endforelse

@if ($pagination && $pagination->hasPages())
    <div style="margin-top:16px">{{ $pagination->links() }}</div>
@endif
@endsection
