@extends('layouts.app')

@section('content')
@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canOpenTickets = auth()->user()?->hasPermission('manage_tickets');
    $canSeeRouterUsers = auth()->user()?->hasPermission('view_unmanaged_router_users');
@endphp
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
    <div class="card stat"><span class="muted">Parties</span><strong>{{ $totalCustomers }}</strong></div>
    <div class="card stat"><span class="muted">Active</span><strong>{{ $activeCustomers }}</strong></div>
    <div class="card stat"><span class="muted">Income</span><strong>{{ number_format($monthlyIncome) }}</strong></div>
    <div class="card stat"><span class="muted">Due</span><strong>{{ number_format($totalDue) }}</strong></div>
    <div class="card stat"><span class="muted">Open Tickets</span><strong>{{ $openTickets }}</strong></div>
    <div class="card stat"><span class="muted">Low Stock</span><strong>{{ $lowStockProducts }}</strong></div>
</div>

@if ($canSeeRouterUsers)
    @php $routerUserCount = $unmanagedRouterUsers->flatten(1)->count(); @endphp
    <section class="card" style="margin-top:16px" id="router-users-not-in-app">
        <div class="section-head" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
            <div>
                <h2 style="margin-bottom:2px">Router users not in the app <span class="badge {{ $routerUserCount ? 'overdue' : 'paid' }}">{{ $routerUserCount }}</span></h2>
                <div class="muted">
                    PPPoE secrets found on routers with no matching party.
                    Last checked: {{ $unmanagedRouterUsersCheckedAt ? \Illuminate\Support\Carbon::parse($unmanagedRouterUsersCheckedAt)->diffForHumans() : 'never — press Refresh' }}
                </div>
            </div>
            <div class="actions" style="gap:8px">
                <form method="post" action="{{ route('router-users.refresh') }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="dashboard">
                    <button class="btn light" type="submit">Refresh from routers</button>
                </form>
                <a class="btn light" href="{{ route('router-users.index') }}">Open full list</a>
            </div>
        </div>

        @if ($routerUserCount === 0)
            <p class="muted" style="margin:8px 0 0">Every router PPPoE user is linked to a party.</p>
        @else
            <form method="post" action="{{ route('router-users.import') }}" id="dashboard-router-users-form">
                @csrf
                <input type="hidden" name="redirect_to" value="dashboard">
                <div class="actions" style="gap:14px;flex-wrap:wrap;margin:10px 0">
                    <label style="font-weight:400;display:flex;gap:6px;align-items:center"><input type="checkbox" name="never_suspend" value="1" style="width:auto"> Special ISP customer</label>
                    <label style="font-weight:400;display:flex;gap:6px;align-items:center"><input type="checkbox" name="update_existing" value="1" style="width:auto"> Update existing party</label>
                    <button class="btn" type="submit">Add selected as parties</button>
                </div>

                @foreach ($unmanagedRouterUsers as $routerName => $secrets)
                    <h3 style="margin:14px 0 6px">{{ $routerName }} <span class="muted">({{ $secrets->count() }})</span></h3>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th><input type="checkbox" data-select-all-router style="width:auto" aria-label="Select all on {{ $routerName }}"></th>
                                    <th>Router username</th>
                                    <th>Profile</th>
                                    <th>Status on router</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($secrets as $secret)
                                    <tr>
                                        <td><input type="checkbox" name="secret_ids[]" value="{{ $secret->id }}" class="router-user-check" style="width:auto"></td>
                                        <td>{{ $secret->name }}</td>
                                        <td>{{ $secret->profile ?: '—' }}</td>
                                        <td><span class="badge {{ $secret->disabled ? 'inactive' : 'active' }}">{{ $secret->disabled ? 'disabled' : 'enabled' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </form>
            <script>
                document.querySelectorAll('#dashboard-router-users-form [data-select-all-router]').forEach(function (master) {
                    master.addEventListener('change', function () {
                        master.closest('table').querySelectorAll('.router-user-check').forEach(function (cb) { cb.checked = master.checked; });
                    });
                });
            </script>
        @endif
    </section>
@endif

<div class="grid two" style="margin-top:16px">
    <section class="card">
        <h2>Recent Invoices</h2>
        <table>
            <thead><tr><th>Invoice</th><th>Party</th><th>Due</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recentInvoices as $invoice)
                <tr @if ($canOpenInvoices) data-href="{{ route('invoices.show', $invoice) }}" @endif>
                    <td>
                        @if ($canOpenInvoices)
                            <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                        @else
                            {{ $invoice->invoice_no }}
                        @endif
                    </td>
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
            <thead><tr><th>Subject</th><th>Party</th><th>Priority</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recentTickets as $ticket)
                <tr @if ($canOpenTickets) data-href="{{ route('tickets.show', $ticket) }}" @endif>
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
