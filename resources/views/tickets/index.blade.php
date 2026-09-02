@extends('layouts.app')

@section('main_class', 'olt-onus-wide')

@section('content')
@php
    $rxText = static fn ($value) => $value === null ? '—' : number_format((float) $value, 2);
    $oltOnuText = static fn ($onu) => $onu
        ? trim(($onu->olt_name ?: '—').' - '.($onu->pon_port ?? '—').'/'.($onu->onu_id ?? '—'))
        : '—';
    $ticketStatuses = ['open', 'processing', 'resolved', 'closed'];
@endphp
<style>
    tr.is-bulk-selected > td { background: #e7f7ef; }
    #ticketBulkForm select, #ticketBulkForm input[type="text"] { min-height: 34px; }
</style>
<div class="topbar">
    <div><h1>Support Tickets</h1><div class="muted">Party complaints and authorized staff work</div></div>
    <a class="btn" href="{{ route('tickets.create') }}">Add Ticket</a>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Subject, description, party, phone, connection ID, authorized"></div>
    <div><label>Priority</label><select name="priority"><option value="">All priorities</option>@foreach(['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
    <div><label>Status</label><select name="status"><option value="">Not closed (default)</option><option value="all" @selected(request('status') === 'all')>All statuses</option>@foreach($ticketStatuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
    <div><label>Authorized</label><select name="assigned"><option value="">All authorized</option><option value="unassigned" @selected(request('assigned') === 'unassigned')>Unassigned</option>@foreach($technicians as $technician)<option value="{{ $technician->id }}" @selected((string) request('assigned') === (string) $technician->id)>{{ $technician->name }}</option>@endforeach</select></div>
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('tickets.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<form method="post" action="{{ route('tickets.bulk-status.update') }}" id="ticketBulkForm"
      class="card" style="margin:0 0 12px;display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px 14px">
    @csrf
    @method('patch')
    <div><label style="display:block;margin-bottom:5px;font-size:12px">Bulk: set status</label>
        <select name="status" required>
            <option value="">Choose status…</option>
            @foreach($ticketStatuses as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach
        </select>
    </div>
    <div style="flex:1 1 240px"><label style="display:block;margin-bottom:5px;font-size:12px">Note (optional)</label>
        <input type="text" name="note" maxlength="5000" placeholder="Reason / work done — added to each ticket">
    </div>
    <div class="actions">
        <button class="btn secondary" type="submit"><span id="ticketBulkCount">0</span> selected — Update</button>
    </div>
</form>

<table>
    <thead><tr><th style="width:34px"><input type="checkbox" id="ticketBulkAll" aria-label="Select all tickets on this page"></th><th>#</th><th>Party</th><th>Mobile</th><th>OLT/ONU</th><th>Authorized</th><th class="col-center">RX/Update</th><th>Subject</th><th class="col-center">Status</th><th>Action</th></tr></thead>
    <tbody>
    @forelse ($tickets as $ticket)
        <tr data-href="{{ route('tickets.show', $ticket) }}">
            <td><input type="checkbox" class="ticket-bulk-row" name="ids[]" value="{{ $ticket->id }}" form="ticketBulkForm" aria-label="Select ticket {{ $ticket->id }}"></td>
            <td>{{ $tickets->firstItem() + $loop->index }}</td>
            <td><a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->customer->name }}</a></td>
            <td>{{ $ticket->customer->phone ?: '—' }}</td>
            <td>{{ $oltOnuText($ticket->matched_onu ?? null) }}</td>
            <td>{{ $ticket->technician?->name ?? 'Unassigned' }}</td>
            <td class="col-center" @if($ticket->rx_power_updated_at) title="Last update Rx captured {{ $ticket->rx_power_updated_at->format('d/m/Y H:i') }}" @endif>
                {{ $rxText($ticket->rx_power_on_create) }} <span class="muted">/</span> {{ $rxText($ticket->rx_power_on_update) }}
            </td>
            <td><a href="{{ route('tickets.show', $ticket) }}"><strong>{{ $ticket->subject }}</strong></a></td>
            <td class="col-center"><span class="badge {{ $ticket->status }}">{{ $ticket->status }}</span></td>
            <td><a class="btn light" href="{{ route('tickets.show', $ticket) }}">Reply / Update</a></td>
        </tr>
    @empty
        <tr><td colspan="10">No tickets found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $tickets->links() }}</div>

<script>
(function () {
    const form = document.getElementById('ticketBulkForm');
    if (! form) return;
    const rows = [...document.querySelectorAll('.ticket-bulk-row')];
    const all = document.getElementById('ticketBulkAll');
    const count = document.getElementById('ticketBulkCount');

    function sync() {
        const picked = rows.filter((box) => box.checked);
        count.textContent = picked.length;
        rows.forEach((box) => box.closest('tr')?.classList.toggle('is-bulk-selected', box.checked));
        if (all) {
            all.checked = picked.length > 0 && picked.length === rows.length;
            all.indeterminate = picked.length > 0 && picked.length < rows.length;
        }
    }

    all?.addEventListener('change', function () {
        rows.forEach((box) => { box.checked = all.checked; });
        sync();
    });
    rows.forEach((box) => box.addEventListener('change', sync));

    form.addEventListener('submit', function (event) {
        const picked = rows.filter((box) => box.checked).length;
        if (picked === 0) {
            event.preventDefault();
            alert('Select at least one ticket first.');
            return;
        }
        if (! form.status.value) {
            event.preventDefault();
            alert('Choose a status to apply.');
            return;
        }
        if (! confirm(`Set ${picked} ticket(s) to "${form.status.value}"?`)) {
            event.preventDefault();
        }
    });

    sync();
})();
</script>
@endsection
