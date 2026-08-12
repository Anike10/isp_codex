@extends('layouts.app')

@section('content')
@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canOpenWarrantyClaims = auth()->user()?->hasPermission('view_warranty_claims')
        || auth()->user()?->hasPermission('manage_warranty_claims')
        || auth()->user()?->hasPermission('manage_products');
@endphp
@php
    $activeUntil = $customer->activeUntil();
    $daysRemaining = $customer->activeDaysRemaining();
    $totalDue = (float) $customer->invoices->sum('due_amount');
    $netBalance = (float) $customer->account_balance - $totalDue;
    $serviceSubscription = $customer->activeSubscription ?: $customer->subscriptions->sortByDesc('id')->first();
    $assignedRouters = $customer->mikrotikRouters;
    if ($assignedRouters->isEmpty() && $customer->mikrotikRouter) $assignedRouters = collect([$customer->mikrotikRouter]);
    $assignedRouterIds = $assignedRouters->pluck('id')->map(fn ($id) => (int) $id)->all();
@endphp
<style>
    .party-page{max-width:1320px;margin:0 auto}.party-hero{padding:24px;border-radius:16px;color:#fff;background:linear-gradient(122deg,#102a43,#116149 58%,#1d76c9);box-shadow:0 16px 34px rgba(16,42,67,.18)}.party-hero-top{display:flex;gap:18px;justify-content:space-between;align-items:flex-start}.party-kicker{margin:0 0 7px;color:#c9f3e5;font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.party-hero h1{font-size:32px;letter-spacing:-.03em}.party-subtitle{margin:7px 0 0;color:#dce9f7}.party-hero .btn.light{background:rgba(255,255,255,.16);color:#fff}.party-hero .btn.secondary{background:#fff;color:#116149}.party-quick{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:22px}.party-quick-item{min-height:88px;padding:14px;border:1px solid rgba(255,255,255,.16);border-radius:11px;background:rgba(255,255,255,.1)}.party-quick-label{display:block;color:#c4dbef;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.party-quick-value{display:block;margin-top:8px;font-size:17px;font-weight:800;line-height:1.25}.party-quick-meta{display:block;margin-top:4px;color:#c4dbef;font-size:12px;font-weight:700}.party-layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.85fr);gap:16px;margin-top:16px}.party-card{padding:20px;border:1px solid #dce6ef;border-radius:14px;background:#fff;box-shadow:0 5px 16px rgba(15,23,42,.045)}.party-card h2{display:flex;gap:9px;align-items:center;margin-bottom:16px;font-size:18px}.party-card h2:before{width:4px;height:21px;border-radius:99px;background:#116149;content:""}.party-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 18px;margin:0}.party-details>div{padding:11px 0;border-bottom:1px solid #edf1f5}.party-details dt{margin-bottom:4px;color:#667085;font-size:12px;font-weight:700}.party-details dd{margin:0;font-weight:700;line-height:1.45}.party-note{grid-column:1/-1}.party-note dd{padding:10px 12px;border-radius:8px;background:#f7fafc;font-weight:400;white-space:normal}.billing-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.billing-stat{padding:14px;border-radius:10px;background:#f6f9fc}.billing-stat span{display:block;color:#667085;font-size:12px;font-weight:700}.billing-stat strong{display:block;margin-top:6px;font-size:21px}.validity-panel{margin-top:18px;padding:15px;border:1px solid #bce6d4;border-radius:10px;background:#f0fbf6}.validity-panel .muted{margin:7px 0 0}.validity-details{margin:10px 0 0;font-size:13px;line-height:1.5}.validity-editor{margin-top:13px;padding-top:12px;border-top:1px solid #cfeade}.validity-editor summary{color:#175cd3;font-weight:800;cursor:pointer}.validity-form{display:grid;grid-template-columns:180px minmax(220px,1fr) auto;gap:9px;margin-top:10px;align-items:end}.validity-form label{font-size:12px}.party-section{margin-top:16px;border-radius:14px;border-color:#dce6ef;box-shadow:0 5px 16px rgba(15,23,42,.04)}.party-section h2{display:flex;align-items:center;gap:9px}.party-section h2:before{width:4px;height:21px;border-radius:99px;background:#1d76c9;content:""}@media(max-width:800px){.party-hero{padding:18px}.party-hero-top,.party-layout{grid-template-columns:1fr;display:grid}.party-quick{grid-template-columns:repeat(2,minmax(0,1fr))}.party-details{grid-template-columns:1fr}.validity-form{grid-template-columns:1fr}.party-hero .actions>*{width:auto}.party-hero h1{font-size:26px}}@media(max-width:460px){.party-quick,.billing-grid{grid-template-columns:1fr}.party-hero .actions>*{width:100%}}
</style>
<style>
    .reseller-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:16px}
    .party-quick-action{display:inline-block;margin-top:7px;color:#fff;font-size:12px;font-weight:800;text-decoration:underline}.router-target-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:10px 0}.router-target-option{display:flex;gap:8px;align-items:flex-start;padding:9px;border:1px solid #dce6ef;border-radius:8px;background:#fff}.router-target-option input{width:auto;margin-top:3px}.router-target-option small{display:block;color:#667085}.validity-editor.danger summary{color:#b42318}
    @media(max-width:800px){.reseller-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.router-target-grid{grid-template-columns:1fr}}
    @media(max-width:460px){.reseller-stats{grid-template-columns:1fr}}
</style>

<div class="party-page">
    <section class="party-hero">
        <div class="party-hero-top">
            <div><p class="party-kicker">Party profile</p><h1>{{ $customer->name }}</h1><p class="party-subtitle">{{ $customer->connection_id ?: 'Product-only party' }} · {{ $customer->phone }}</p></div>
            <div class="actions"><a class="btn" href="{{ route('customers.payments.create', $customer) }}">Record Payment</a><a class="btn secondary" href="{{ route('customers.edit', $customer) }}">Edit Party</a><a class="btn light" href="{{ route('customers.index') }}">All Parties</a></div>
        </div>
        <div class="party-quick">
            <div class="party-quick-item"><span class="party-quick-label">Service status</span><span class="party-quick-value"><span class="badge {{ $customer->status }}">{{ ucfirst($customer->status) }}</span></span>@if($customer->status === 'active')<a class="party-quick-action" href="#temporary-inactive">Temporary inactive</a>@else<a class="party-quick-action" href="#temporary-active">Temporary active</a>@endif</div>
            <div class="party-quick-item"><span class="party-quick-label">Validity until</span><span class="party-quick-value">{{ $activeUntil?->format('d/m/Y') ?? 'Not set' }}</span>@if ($daysRemaining !== null)<span class="party-quick-meta">{{ $daysRemaining < 0 ? 'Expired '.abs($daysRemaining).' day(s) ago' : ($daysRemaining === 0 ? 'Last valid day' : $daysRemaining.' day(s) left') }}</span>@endif</div>
            <div class="party-quick-item"><span class="party-quick-label">Current due</span><span class="party-quick-value">৳ {{ number_format($totalDue, 2) }}</span></div>
            <div class="party-quick-item"><span class="party-quick-label">Package</span><span class="party-quick-value">@if($serviceSubscription?->package){{ $serviceSubscription->package->name }} (&#2547; {{ number_format((float) $serviceSubscription->package->monthly_price, 2) }})@else Not assigned @endif</span></div>
        </div>
    </section>

    <div class="party-layout">
        <section class="party-card">
            <h2>Service & Network</h2>
            <dl class="party-details">
                <div><dt>Party type</dt><dd>@if ($customer->is_customer)<span class="badge active">Customer</span>@endif @if ($customer->is_vendor)<span class="badge pending">Vendor</span>@endif @if ($customer->is_reseller)<span class="badge active">Reseller</span>@endif</dd></div>
                <div><dt>Assigned reseller</dt><dd>@if($customer->reseller)<strong>{{ $customer->reseller->name }}</strong><div class="muted">{{ $customer->reseller->phone }} · Commission {{ number_format((float) $customer->reseller->reseller_commission_percent, 2) }}%</div>@else Direct / no reseller @endif</dd></div>
                @if($customer->is_reseller)<div><dt>Reseller commission</dt><dd><strong>{{ number_format((float) $customer->reseller_commission_percent, 2) }}%</strong></dd></div>@endif
                <div><dt>Grace period</dt><dd>{{ $customer->grace_used_at ? 'Used '.$customer->grace_days.' day(s), until '.$customer->grace_until?->format('d/m/Y') : 'Not used' }}</dd></div>
                <div><dt>MikroTik user ID</dt><dd>{{ $customer->mikrotik_username ?? $customer->connection_id ?? 'Not assigned' }}</dd></div>
                <div><dt>MikroTik profile</dt><dd>@if($serviceSubscription?->package){{ $serviceSubscription->package->mikrotik_profile ?: 'No saved profile' }} (&#2547; {{ number_format((float) $serviceSubscription->package->monthly_price, 2) }})@else No saved profile @endif</dd></div>
                <div><dt>IP assignment</dt><dd>{{ $customer->use_fixed_ip ? 'Fixed · '.($customer->fixed_ip_address ?: 'Not set') : 'Dynamic · '.($customer->learned_ip_address ?: 'Waiting for next connection') }}</dd></div>
                <div><dt>Last connected IP</dt><dd>{{ $customer->last_connected_ip ?? 'Not learned yet' }}</dd></div>
                <div><dt>Last connected MAC</dt><dd>{{ $customer->last_connected_mac ?? 'Not learned yet' }}</dd></div>
                <div><dt>Last connected at</dt><dd>{{ $customer->last_connected_at?->format('d/m/Y H:i:s') ?? 'Not learned yet' }}</dd></div>
                <div class="party-note"><dt>MikroTik targets</dt><dd>
                    @if(! $customer->mikrotik_username && ! $customer->connection_id)
                        Not assigned. Add a Connection ID first.
                    @else
                        <strong>{{ $assignedRouters->isNotEmpty() ? $assignedRouters->map(fn ($router) => $router->name.' · '.$router->ip_address.':'.$router->api_port)->join(', ') : 'Legacy: all active MikroTik routers' }}</strong>
                        <form method="post" action="{{ route('customers.mikrotik-targets.update', $customer) }}">
                            @csrf
                            <div class="router-target-grid">
                                @foreach($routers as $router)
                                    <label class="router-target-option">
                                        <input type="checkbox" name="mikrotik_router_ids[]" value="{{ $router->id }}" @checked(in_array($router->id, old('mikrotik_router_ids', $assignedRouterIds), true))>
                                        <span><strong>{{ $router->name }}</strong><small>{{ $router->ip_address }}:{{ $router->api_port }} · {{ ucfirst($router->status) }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="muted">Choose one or more targets. Saving creates or updates this PPPoE user on every selected active router.</div>
                            <button class="btn secondary" type="submit" style="margin-top:9px">Save &amp; sync targets</button>
                        </form>
                    @endif
                </dd></div>
            </dl>
            <div class="validity-panel">
                <strong>Validity: {{ $activeUntil?->format('d/m/Y') ?? 'Not set' }}</strong>
                <span class="badge {{ $daysRemaining !== null && $daysRemaining < 0 ? 'overdue' : 'active' }}">{{ $daysRemaining === null ? 'No paid period' : ($daysRemaining < 0 ? 'Expired '.abs($daysRemaining).' days ago' : ($daysRemaining === 0 ? 'Last day' : $daysRemaining.' days left')) }}</span>
                @if ($customer->hasActiveGracePeriod()) <span class="badge pending">Grace period active</span> @endif
                <div class="validity-details">{!! $customer->service_validity_note ? nl2br(e($customer->service_validity_note)) : 'No payment-based validity calculation recorded yet.' !!}</div>
                @if($serviceSubscription?->package && (float) $customer->account_balance > 0)
                    @php
                        $renewalPrice = (float) $serviceSubscription->package->monthly_price;
                    @endphp
                    <form method="post" action="{{ route('customers.advance-renewal.store', $customer) }}" style="margin-top:12px" onsubmit="return confirm('Create the next package invoice and renew one month from advance balance?')">
                        @csrf
                        <button class="btn secondary" type="submit" @disabled((float) $totalDue > 0 || (float) $customer->account_balance < $renewalPrice)>Renew 1 month from advance (&#2547; {{ number_format($renewalPrice, 2) }})</button>
                        <div class="muted" style="margin-top:7px">Available: &#2547; {{ number_format((float) $customer->account_balance, 2) }}. This creates and pays the next package invoice.</div>
                    </form>
                @endif
                <details class="validity-editor"><summary>Force validity date (note required)</summary><form method="post" action="{{ route('customers.service-validity.update', $customer) }}" class="validity-form">@csrf <div><label>New validity date</label><input type="date" name="service_valid_until" value="{{ old('service_valid_until', $customer->service_valid_until?->format('Y-m-d')) }}" required></div><div><label>Reason / note</label><input type="text" name="validity_note" value="{{ old('validity_note') }}" placeholder="Reason is required" required></div><button class="btn secondary" type="submit">Save</button></form></details>
                @if($customer->status === 'active')
                    <details class="validity-editor danger" id="temporary-inactive"><summary>Temporary inactive (keep validity &amp; grace)</summary><form method="post" action="{{ route('customers.force-inactive', $customer) }}" class="validity-form" onsubmit="return confirm('Temporarily make this service inactive now? Validity and grace data will remain unchanged.')">@csrf <div style="grid-column:span 2"><label>Reason / note</label><input type="text" name="inactive_note" value="{{ old('inactive_note') }}" placeholder="Reason is required" required></div><button class="btn danger" type="submit">Temporary inactive</button></form></details>
                @else
                    <details class="validity-editor" id="temporary-active"><summary>Temporary active (keep validity &amp; grace)</summary><form method="post" action="{{ route('customers.force-active', $customer) }}" class="validity-form" onsubmit="return confirm('Temporarily make this service active now? Validity and grace data will remain unchanged.')">@csrf <div style="grid-column:span 2"><label>Reason / note</label><input type="text" name="active_note" value="{{ old('active_note') }}" placeholder="Reason is required" required></div><button class="btn secondary" type="submit">Temporary active</button></form></details>
                @endif
            </div>
            @if (($customer->status === 'inactive' || ($daysRemaining !== null && $daysRemaining < 0)) && ! $customer->grace_used_at)
                @if ($customer->subscriptions->isNotEmpty())<form method="post" action="{{ route('customers.grace-period', $customer) }}" class="actions" style="margin-top:14px">@csrf <input type="number" name="grace_days" min="1" max="365" placeholder="Grace days" required><button class="btn secondary" type="submit">Give Grace Period</button></form>@else <p><a class="btn light" href="{{ route('customers.edit', $customer) }}">Assign package before giving grace</a></p>@endif
            @endif
        </section>
        <aside class="party-card"><h2>Billing snapshot</h2><div class="billing-grid"><div class="billing-stat"><span>Invoice count</span><strong>{{ $customer->invoices->count() }}</strong></div><div class="billing-stat"><span>Total invoiced</span><strong>৳ {{ number_format($customer->invoices->sum('total'), 2) }}</strong></div><div class="billing-stat"><span>Total due</span><strong>৳ {{ number_format($totalDue, 2) }}</strong></div><div class="billing-stat"><span>Advance balance</span><strong style="color:{{ $customer->account_balance > 0 ? '#027a48' : 'inherit' }}">৳ {{ number_format($customer->account_balance, 2) }}</strong></div></div><dl class="party-details" style="margin-top:14px"><div><dt>Net balance</dt><dd style="color:{{ $netBalance < 0 ? '#b42318' : '#027a48' }}">৳ {{ number_format($netBalance, 2) }}</dd></div><div><dt>Email</dt><dd>{{ $customer->email ?? 'Not provided' }}</dd></div><div><dt>Special ISP customer</dt><dd>{{ $customer->never_suspend ? 'Yes · never auto-close' : 'No' }}</dd></div><div class="party-note"><dt>Address</dt><dd>{{ $customer->address ?: 'Not provided' }}</dd></div><div class="party-note"><dt>Party note</dt><dd>{!! $customer->notes ? nl2br(e($customer->notes)) : 'No note' !!}</dd></div></dl></aside>
    </div>

    <section class="card party-section">
        <h2>Contact & Account</h2>
        <dl class="party-details">
            <div><dt>Phone</dt><dd>{{ $customer->phone ?: 'Not provided' }}</dd></div>
            <div><dt>Email</dt><dd>@if($customer->email)<a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>@else Not provided @endif</dd></div>
            <div><dt>Connection ID</dt><dd>{{ $customer->connection_id ?: 'Not assigned' }}</dd></div>
            <div><dt>Account status</dt><dd><span class="badge {{ $customer->status }}">{{ ucfirst($customer->status) }}</span></dd></div>
            <div><dt>Created</dt><dd>{{ $customer->created_at?->format('d/m/Y, h:i A') ?? 'Not available' }}</dd></div>
            <div><dt>Last updated</dt><dd>{{ $customer->updated_at?->format('d/m/Y, h:i A') ?? 'Not available' }}</dd></div>
            <div class="party-note"><dt>Address</dt><dd>{{ $customer->address ?: 'Not provided' }}</dd></div>
            <div class="party-note"><dt>Party note</dt><dd>{!! $customer->notes ? nl2br(e($customer->notes)) : 'No note' !!}</dd></div>
        </dl>
    </section>

    @if ($customer->is_reseller)
        <section class="card party-section">
            <div class="section-head">
                <div><h2>Reseller Details</h2></div>
                @if (auth()->user()?->hasPermission('manage_users') && $customer->loginUsers->isEmpty())
                    <a class="btn" href="{{ route('users.create', ['reseller_id' => $customer->id]) }}">Create Reseller Login</a>
                @endif
            </div>
            <div class="reseller-stats">
                <div class="billing-stat"><span>Wallet balance</span><strong>&#2547; {{ number_format((float) $customer->account_balance, 2) }}</strong></div>
                <div class="billing-stat"><span>Daily payment limit</span><strong>@if($customer->reseller_daily_payment_limit === null) Unlimited @else &#2547; {{ number_format((float) $customer->reseller_daily_payment_limit, 2) }} @endif</strong></div>
                <div class="billing-stat"><span>Commission</span><strong>{{ number_format((float) $customer->reseller_commission_percent, 2) }}%</strong></div>
                <div class="billing-stat"><span>Assigned parties</span><strong>{{ $customer->resellerCustomers->count() }}</strong></div>
            </div>
            <dl class="party-details" style="margin-bottom:16px">
                <div class="party-note"><dt>Portal login</dt><dd>
                    @forelse ($customer->loginUsers as $loginUser)
                        <strong>{{ $loginUser->name }}</strong> · {{ $loginUser->email }}
                        @if($loginUser->roles->isNotEmpty())<span class="muted">({{ $loginUser->roles->pluck('name')->join(', ') }})</span>@endif
                        @if(!$loop->last)<br>@endif
                    @empty
                        No login user is linked to this reseller.
                    @endforelse
                </dd></div>
            </dl>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Party</th><th>Connection ID</th><th>Phone</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse ($customer->resellerCustomers as $resellerCustomer)
                        <tr>
                            <td>{{ $resellerCustomer->name }}</td>
                            <td>{{ $resellerCustomer->connection_id ?: 'N/A' }}</td>
                            <td>{{ $resellerCustomer->phone ?: 'Not provided' }}</td>
                            <td>{{ $resellerCustomer->email ?: 'Not provided' }}</td>
                            <td><span class="badge {{ $resellerCustomer->status }}">{{ ucfirst($resellerCustomer->status) }}</span></td>
                            <td><a class="btn light" href="{{ route('customers.show', $resellerCustomer) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No party is assigned to this reseller.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

<section class="card party-section" style="margin-top:16px">
    <h2>Assets & Warranty</h2>
    <table>
        <thead><tr><th>Product</th><th>Serial</th><th>Invoice</th><th>Sold Date</th><th>Warranty</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($customer->productSerials as $serial)
            @php
                $openClaim = $serial->warrantyClaims->first(fn ($claim) => in_array($claim->status, \App\Models\WarrantyClaim::OPEN_STATUSES, true));
                $warrantyLabel = $serial->warranty_until
                    ? ($serial->warranty_until->copy()->endOfDay()->gte(now()) ? 'In warranty until '.$serial->warranty_until->format('d/m/Y') : 'Expired '.$serial->warranty_until->format('d/m/Y'))
                    : 'No warranty';
            @endphp
            <tr>
                <td>{{ $serial->product?->name ?? 'N/A' }}</td>
                <td><span class="badge">{{ $serial->serial_number }}</span></td>
                <td>
                    @if ($serial->invoice)
                        @if ($canOpenInvoices)
                            <a href="{{ route('invoices.show', $serial->invoice) }}">{{ $serial->invoice->invoice_no }}</a>
                        @else
                            {{ $serial->invoice->invoice_no }}
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $serial->sold_at?->format('d/m/Y') ?? 'N/A' }}</td>
                <td>{{ $warrantyLabel }}</td>
                <td>
                    @if ($openClaim && $canOpenWarrantyClaims)
                        <a class="badge pending" href="{{ route('warranty-claims.show', $openClaim) }}">{{ str_replace('_', ' ', $openClaim->status) }}</a>
                    @elseif ($openClaim)
                        <span class="badge pending">{{ str_replace('_', ' ', $openClaim->status) }}</span>
                    @else
                        {{ str_replace('_', ' ', $serial->status) }}
                    @endif
                </td>
                <td>
                    @if (auth()->user()?->hasPermission('manage_warranty_claims') && ! $openClaim)
                        <a class="btn light" href="{{ route('warranty-claims.create', ['product_serial_id' => $serial->id]) }}">Warranty Claim</a>
                    @elseif ($openClaim && $canOpenWarrantyClaims)
                        <a class="btn light" href="{{ route('warranty-claims.show', $openClaim) }}">View Claim</a>
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No sold serial assets found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card party-section" style="margin-top:16px">
    <h2>Warranty Claims</h2>
    <table>
        <thead><tr><th>Claim</th><th>Product</th><th>Serial</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        @forelse ($customer->warrantyClaims as $claim)
            <tr>
                <td>
                    @if ($canOpenWarrantyClaims)
                        <a href="{{ route('warranty-claims.show', $claim) }}">{{ $claim->claim_no }}</a>
                    @else
                        {{ $claim->claim_no }}
                    @endif
                </td>
                <td>{{ $claim->product?->name ?? 'Manual claim' }}</td>
                <td>{{ $claim->productSerial?->serial_number ?? 'N/A' }}</td>
                <td><span class="badge pending">{{ str_replace('_', ' ', $claim->status) }}</span></td>
                <td>{{ $claim->claim_date?->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No warranty claims yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card party-section" style="margin-top:16px">
    <h2>Invoices</h2>
    <table>
        <thead><tr><th>Invoice</th><th>Month</th><th>Total</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($customer->invoices as $invoice)
            <tr>
                <td>
                    @if ($canOpenInvoices)
                        <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                    @else
                        {{ $invoice->invoice_no }}
                    @endif
                </td>
                <td>{{ $invoice->formatted_billing_month }}</td>
                <td>{{ number_format($invoice->total, 2) }}</td>
                <td>{{ number_format($invoice->due_amount, 2) }}</td>
                <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5">No invoices yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

@if($customer->is_reseller)
<section class="card party-section" style="margin-top:16px">
    <h2>Commission Change History</h2>
    <table>
        <thead><tr><th>#</th><th>Changed At</th><th>Previous</th><th>New</th><th>Changed By</th><th>Note</th></tr></thead>
        <tbody>
        @forelse($customer->commissionHistories as $history)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $history->changed_at?->format('d/m/Y H:i') }}</td><td>{{ $history->old_percent === null ? 'Initial' : number_format((float) $history->old_percent, 2).'%' }}</td><td>{{ number_format((float) $history->new_percent, 2) }}%</td><td>{{ $history->changedByUser?->name ?? 'System' }}</td><td>{{ $history->note ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="6">No commission changes recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endif

@include('partials.record_versions', ['versions' => $versions])

<section class="card party-section" style="margin-top:16px">
    <h2>Advance Balance History</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th><th>Reference</th><th>Note</th></tr></thead>
        <tbody>
        @forelse ($customer->balanceTransactions as $transaction)
            <tr>
                <td>{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                <td><span class="badge {{ $transaction->direction === 'credit' ? 'active' : 'due' }}">{{ $transaction->direction }}</span></td>
                <td>{{ number_format($transaction->amount, 2) }}</td>
                <td>{{ number_format($transaction->balance_after, 2) }}</td>
                <td>{{ $transaction->reference ?? 'N/A' }}</td>
                <td>{{ $transaction->note ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No advance balance history yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
</div>
@endsection
