@extends('layouts.app')

@section('content')
@php
    $wallet = (float) $reseller->account_balance;
    $canSpendToday = $dailyRemaining === null ? $wallet : min($wallet, $dailyRemaining);
    $totalCustomerDue = (float) $customers->sum('total_due_amount');
    $dueInvoiceTotal = (float) $dueInvoices->sum('due_amount');
@endphp

<style>
    .reseller-page{max-width:1440px;margin:0 auto}
    .reseller-hero{position:relative;overflow:hidden;padding:26px;border-radius:18px;color:#fff;background:linear-gradient(125deg,#102a43 0%,#116149 56%,#1d76c9 100%);box-shadow:0 18px 38px rgba(16,42,67,.2)}
    .reseller-hero:after{position:absolute;right:-70px;bottom:-110px;width:280px;height:280px;border:45px solid rgba(255,255,255,.08);border-radius:50%;content:""}
    .reseller-hero-head{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:flex-start;gap:20px}
    .reseller-kicker{margin:0 0 7px;color:#bdebdc;font-size:12px;font-weight:800;letter-spacing:.11em;text-transform:uppercase}
    .reseller-hero h1{margin:0;font-size:32px;letter-spacing:-.03em}
    .reseller-contact{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;color:#d8e9f6;font-size:13px}
    .reseller-hero .btn{position:relative;z-index:2}
    .reseller-hero .btn.light{border-color:rgba(255,255,255,.3);background:rgba(255,255,255,.13);color:#fff}
    .reseller-hero .btn.secondary{background:#fff;color:#116149}
    .reseller-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:11px;margin-top:18px}
    .reseller-stat{position:relative;z-index:1;min-height:108px;padding:15px;border:1px solid rgba(255,255,255,.16);border-radius:12px;background:rgba(255,255,255,.1);backdrop-filter:blur(4px)}
    .reseller-stat span{display:block;color:#c9ddef;font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
    .reseller-stat strong{display:block;margin-top:10px;font-size:22px;line-height:1.2}
    .reseller-stat small{display:block;margin-top:5px;color:#c9ddef;font-size:11px}
    .dashboard-section{margin-top:16px;padding:0;overflow:hidden;border-color:#dbe5ee;border-radius:14px;box-shadow:0 6px 18px rgba(15,23,42,.045)}
    .dashboard-section-head{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:18px 20px;border-bottom:1px solid #e6edf3;background:linear-gradient(180deg,#fff,#f8fbfd)}
    .dashboard-section-head h2{display:flex;align-items:center;gap:9px;margin:0;font-size:18px}
    .dashboard-section-head h2:before{width:4px;height:21px;border-radius:99px;background:#116149;content:""}
    .dashboard-section-head p{margin:5px 0 0;color:#667085;font-size:12px}
    .section-total{padding:8px 11px;border-radius:9px;background:#edf8f3;color:#116149;font-size:13px;font-weight:800;white-space:nowrap}
    .dashboard-table{overflow-x:auto}
    .dashboard-table table{margin:0;border:0}
    .dashboard-table th{padding:11px 14px;background:#eef4f8;color:#475467;font-size:11px;letter-spacing:.04em;text-transform:uppercase;white-space:nowrap}
    .dashboard-table td{padding:13px 14px;vertical-align:middle}
    .money{font-weight:800;white-space:nowrap}.money.due{color:#b42318}.money.credit{color:#027a48}
    .empty-state{padding:30px!important;text-align:center;color:#667085}
    .payment-form{display:flex;gap:7px;align-items:center;flex-wrap:nowrap}.payment-form input{width:120px}
    .commission-current{display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid #e6edf3;background:#f0fbf6}
    .commission-current strong{font-size:26px;color:#116149}.commission-current span{color:#475467;font-size:13px}
    @media(max-width:1100px){.reseller-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:700px){.reseller-hero{padding:19px}.reseller-hero-head{display:grid}.reseller-hero h1{font-size:26px}.reseller-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-section-head{align-items:flex-start}.reseller-hero .actions>*{width:auto}}
    @media(max-width:430px){.reseller-stats{grid-template-columns:1fr}.dashboard-section-head{display:grid}.reseller-hero .actions>*{width:100%}}
</style>

<div class="reseller-page">
    <section class="reseller-hero">
        <div class="reseller-hero-head">
            <div>
                <p class="reseller-kicker">{{ $isAdminView ? 'Reseller management' : 'Reseller portal' }}</p>
                <h1>{{ $reseller->name }}</h1>
                <div class="reseller-contact">
                    <span>{{ $reseller->phone ?: 'No phone' }}</span>
                    <span>•</span>
                    <span>{{ $reseller->email ?: 'No email' }}</span>
                    <span>•</span>
                    <span class="badge {{ $reseller->status }}">{{ ucfirst($reseller->status) }}</span>
                    @if($isAdminView)<span>•</span><span>Portal login: {{ $reseller->loginUsers->pluck('email')->join(', ') ?: 'Not created' }}</span>@endif
                </div>
            </div>
            @if ($isAdminView)
                <div class="actions">
                    <a class="btn light" href="{{ route('resellers.index') }}">All Resellers</a>
                    @if (auth()->user()?->hasPermission('manage_customers'))
                        <a class="btn secondary" href="{{ route('customers.payments.create', $reseller) }}">Add Wallet Balance</a>
                        <a class="btn light" href="{{ route('customers.edit', $reseller) }}">Edit Reseller</a>
                    @endif
                    @if (auth()->user()?->hasPermission('manage_users') && $reseller->loginUsers->isEmpty())
                        <a class="btn light" href="{{ route('users.create', ['reseller_id' => $reseller->id]) }}">Create Login</a>
                    @endif
                </div>
            @endif
        </div>

        <div class="reseller-stats">
            <div class="reseller-stat"><span>Wallet Balance</span><strong>৳ {{ number_format($wallet, 2) }}</strong><small>Current prepaid balance</small></div>
            <div class="reseller-stat"><span>Daily Limit</span><strong>{{ $dailyLimit === null ? 'Unlimited' : '৳ '.number_format($dailyLimit, 2) }}</strong><small>Maximum daily spending</small></div>
            <div class="reseller-stat"><span>Used Today</span><strong>৳ {{ number_format($spentToday, 2) }}</strong><small>Wallet payment today</small></div>
            <div class="reseller-stat"><span>Available Today</span><strong>৳ {{ number_format($canSpendToday, 2) }}</strong><small>Spendable right now</small></div>
            <div class="reseller-stat"><span>Assigned Parties</span><strong>{{ $reseller->reseller_customers_count }}</strong><small>Total due ৳ {{ number_format($totalCustomerDue, 2) }}</small></div>
            <div class="reseller-stat"><span>Commission</span><strong>{{ number_format((float) $reseller->reseller_commission_percent, 2) }}%</strong><small>Applied to new invoices</small></div>
        </div>
    </section>

    <section class="card dashboard-section">
        <div class="dashboard-section-head">
            <div><h2>Due Invoices</h2><p>Partial or full payment is deducted from the reseller wallet immediately.</p></div>
            <div class="section-total">{{ $dueInvoices->count() }} invoice(s) · ৳ {{ number_format($dueInvoiceTotal, 2) }}</div>
        </div>
        <div class="dashboard-table">
            <table>
                <thead><tr><th>#</th><th>Customer</th><th>Invoice</th><th>Billing Month</th><th>Due Date</th><th>Due</th><th>Pay from Wallet</th></tr></thead>
                <tbody>
                @forelse ($dueInvoices as $invoice)
                    @php $suggested = min((float) $invoice->due_amount, $canSpendToday); @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $invoice->customer->name }}</strong><div class="muted">{{ $invoice->customer->connection_id ?? $invoice->customer->phone }}</div></td>
                        <td>{{ $invoice->invoice_no }}</td>
                        <td>{{ $invoice->formatted_billing_month }}</td>
                        <td>{{ $invoice->due_date?->format('d M Y') ?? 'N/A' }}</td>
                        <td class="money due">৳ {{ number_format((float) $invoice->due_amount, 2) }}</td>
                        <td>
                            <form method="post" action="{{ $isAdminView ? route('resellers.invoices.pay', [$reseller, $invoice]) : route('reseller.invoices.pay', $invoice) }}" class="payment-form">
                                @csrf
                                <input type="hidden" name="operation_key" value="{{ (string) Illuminate\Support\Str::uuid() }}">
                                <input type="number" name="amount" min="1" max="{{ (float) $invoice->due_amount }}" step="0.01" value="{{ $suggested > 0 ? number_format($suggested, 2, '.', '') : '' }}" required @disabled($suggested <= 0)>
                                <button class="btn secondary" type="submit" @disabled($suggested <= 0)>Pay</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="7">No due invoices for assigned customers.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card dashboard-section">
        <div class="dashboard-section-head">
            <div><h2>Assigned Customers</h2><p>Packages, balances and service status of every assigned party.</p></div>
            <div class="section-total">{{ $customers->count() }} customer(s)</div>
        </div>
        <div class="dashboard-table">
            <table>
                <thead><tr><th>#</th><th>Customer</th><th>Connection ID</th><th>Package</th><th>Total Due</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $customer->name }}</strong><div class="muted">{{ $customer->phone }}</div></td>
                        <td>{{ $customer->connection_id ?? '—' }}</td>
                        <td>@if($customer->activeSubscription?->package)<strong>{{ $customer->activeSubscription->package->name }}</strong><div class="muted">৳ {{ number_format((float) $customer->activeSubscription->package->monthly_price, 2) }}</div>@else No package @endif</td>
                        <td class="money {{ (float) ($customer->total_due_amount ?? 0) > 0 ? 'due' : 'credit' }}">৳ {{ number_format((float) ($customer->total_due_amount ?? 0), 2) }}</td>
                        <td><span class="badge {{ $customer->status }}">{{ ucfirst($customer->status) }}</span></td>
                        <td class="actions">
                            @if(!$isAdminView)
                                <form method="post" action="{{ route('reseller.customers.invoices.store', $customer) }}">@csrf<button class="btn light" type="submit">Generate Invoice</button></form>
                                <a class="btn secondary" href="{{ route('reseller.customers.payments.create', $customer) }}">Payment Entry</a>
                                @if($customer->latestInvoice)<a class="btn light" target="_blank" href="{{ route('reseller.invoices.print', $customer->latestInvoice) }}">Print Invoice</a>@endif
                            @elseif(auth()->user()?->hasPermission('manage_customers'))
                                <a class="btn light" href="{{ route('customers.show', $customer) }}">View Party</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="7">No customers are assigned to this reseller.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card dashboard-section">
        <div class="dashboard-section-head">
            <div><h2>Wallet Transactions</h2><p>Latest 50 top-ups and customer payments.</p></div>
        </div>
        <div class="dashboard-table">
            <table>
                <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Customer / Invoice</th><th>Reference</th><th>Credit</th><th>Debit</th><th>Balance</th></tr></thead>
                <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                        <td>{{ $transaction->payment_method === 'reseller_wallet' ? 'Customer payment' : 'Wallet top-up' }}</td>
                        <td>{{ $transaction->invoice?->customer?->name ?? '—' }}@if($transaction->invoice)<div class="muted">{{ $transaction->invoice->invoice_no }}</div>@endif</td>
                        <td>{{ $transaction->reference ?? '—' }}</td>
                        <td class="money credit">{{ $transaction->direction === 'credit' ? '৳ '.number_format((float) $transaction->amount, 2) : '—' }}</td>
                        <td class="money due">{{ $transaction->direction === 'debit' ? '৳ '.number_format((float) $transaction->amount, 2) : '—' }}</td>
                        <td class="money">৳ {{ number_format((float) $transaction->balance_after, 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="empty-state" colspan="8">No wallet transactions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card dashboard-section">
        <div class="dashboard-section-head">
            <div><h2>Commission Change History</h2><p>Every commission adjustment remains auditable.</p></div>
        </div>
        <div class="commission-current"><strong>{{ number_format((float) $reseller->reseller_commission_percent, 2) }}%</strong><span>Current commission for new reseller invoices</span></div>
        <div class="dashboard-table">
            <table>
                <thead><tr><th>#</th><th>Changed At</th><th>Previous</th><th>New</th><th>Changed By</th><th>Note</th></tr></thead>
                <tbody>
                @forelse($reseller->commissionHistories as $history)
                    <tr><td>{{ $loop->iteration }}</td><td>{{ $history->changed_at?->format('d M Y, h:i A') }}</td><td>{{ $history->old_percent === null ? 'Initial' : number_format((float)$history->old_percent, 2).'%' }}</td><td><strong>{{ number_format((float)$history->new_percent, 2) }}%</strong></td><td>{{ $history->changedByUser?->name ?? 'System' }}</td><td>{{ $history->note ?: '—' }}</td></tr>
                @empty
                    <tr><td class="empty-state" colspan="6">No commission changes recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
