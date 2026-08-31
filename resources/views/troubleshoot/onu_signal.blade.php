@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; ONU Signal History</h1>
        <div class="muted">
            Stored ONU Rx/Tx optical power for every party with samples — last <strong>{{ $days }}</strong> day(s), sampled every {{ $intervalHours }}h.
            The green band is −15 to −25 dBm (normal). Use one Rx/Tx switch below for every graph.
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
    .onu-allsig__card { margin-top: 14px; }
    .onu-allsig__card h2 { margin: 0 0 8px; font-size: 15px; }
    .onu-allsig__card h2 .muted { font-weight: 400; font-size: 12px; }
</style>

<div class="onu-allsig__bar">
    <span class="muted">Showing {{ $pagination?->firstItem() ?? 0 }}–{{ $pagination?->lastItem() ?? 0 }} of {{ $pagination?->total() ?? 0 }} parties</span>
    <form method="post" action="{{ route('onu-signal.visibility.update') }}" class="onu-signal__show">
        @csrf
        @method('patch')
        <span class="muted">Show on every graph:</span>
        <label><input type="checkbox" name="show_rx" value="1" @checked($showRx)> Rx</label>
        <label><input type="checkbox" name="show_tx" value="1" @checked($showTx)> Tx</label>
        <noscript><button class="btn light" type="submit">Save</button></noscript>
    </form>
</div>

@forelse ($parties as $party)
    <section class="card onu-allsig__card">
        <h2>
            <a href="{{ route('customers.show', $party) }}">{{ $party->name }}</a>
            <span class="muted">· {{ $party->connection_id ?? ('#'.$party->id) }}@if ($party->last_connected_mac) · {{ $party->last_connected_mac }}@endif</span>
        </h2>
        @include('customers._onu_signal_chart', [
            'samples' => $party->onuSamplesWindow,
            'showRx' => $showRx,
            'showTx' => $showTx,
            'showLegend' => true,
        ])
    </section>
@empty
    <div class="card"><p class="muted" style="margin:0">এখনো কোনো পার্টির ONU signal নমুনা জমা হয়নি। সংগ্রহ শুরু হলে (প্রতি {{ $intervalHours }}h) এখানে সব পার্টির গ্রাফ দেখা যাবে।</p></div>
@endforelse

@if ($pagination && $pagination->hasPages())
    <div style="margin-top:16px">{{ $pagination->links() }}</div>
@endif
@endsection
