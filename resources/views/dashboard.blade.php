@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Dashboard</h1>
        <div class="muted">Computer service and internet billing overview</div>
    </div>
    <div class="actions">
        @if (auth()->user()?->hasPermission('download_backup'))
            <a class="btn secondary" href="{{ route('backup.database') }}">Download DB Backup</a>
        @endif
        @if (auth()->user()?->hasPermission('manage_invoices'))
            <form method="post" action="{{ route('invoices.generate') }}" class="actions">
                @csrf
                <input type="month" name="billing_month" value="{{ now()->format('Y-m') }}" required>
                <button class="btn" type="submit">Generate Bills</button>
            </form>
        @endif
    </div>
</div>

<div class="grid stats">
    <div class="card stat"><span class="muted">Customers</span><strong>{{ $totalCustomers }}</strong></div>
    <div class="card stat"><span class="muted">Active</span><strong>{{ $activeCustomers }}</strong></div>
    <div class="card stat"><span class="muted">Income</span><strong>{{ number_format($monthlyIncome) }}</strong></div>
    <div class="card stat"><span class="muted">Due</span><strong>{{ number_format($totalDue) }}</strong></div>
    <div class="card stat"><span class="muted">Open Tickets</span><strong>{{ $openTickets }}</strong></div>
    <div class="card stat"><span class="muted">Low Stock</span><strong>{{ $lowStockProducts }}</strong></div>
</div>

<div class="grid two" style="margin-top:16px">
    <section class="card">
        <h2>Recent Invoices</h2>
        <table>
            <thead><tr><th>Invoice</th><th>Customer</th><th>Due</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recentInvoices as $invoice)
                <tr data-href="{{ route('invoices.show', $invoice) }}">
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a></td>
                    <td>{{ $invoice->customer->name }}</td>
                    <td>{{ number_format($invoice->due_amount, 2) }}</td>
                    <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4">No invoices yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
    <section class="card">
        <h2>Recent Tickets</h2>
        <table>
            <thead><tr><th>Subject</th><th>Customer</th><th>Priority</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recentTickets as $ticket)
                <tr data-href="{{ route('tickets.show', $ticket) }}">
                    <td>{{ $ticket->subject }}</td>
                    <td>{{ $ticket->customer->name }}</td>
                    <td>{{ $ticket->priority }}</td>
                    <td><span class="badge {{ $ticket->status }}">{{ $ticket->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4">No tickets yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
