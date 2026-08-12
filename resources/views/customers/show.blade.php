@extends('layouts.app')

@section('main_class', 'customer-show')
@section('content')

@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canOpenWarrantyClaims = auth()->user()?->hasPermission('view_warranty_claims')
        || auth()->user()?->hasPermission('manage_warranty_claims')
        || auth()->user()?->hasPermission('manage_products');

    $activeUntil = $customer->activeUntil();
    $daysRemaining = $customer->activeDaysRemaining();
    $totalDue = (float) $customer->invoices->sum('due_amount');
    $netBalance = (float) $customer->account_balance - $totalDue;
    $serviceSubscription = $customer->activeSubscription ?: $customer->subscriptions->sortByDesc('id')->first();
    $assignedRouters = $customer->mikrotikRouters;
    if ($assignedRouters->isEmpty() && $customer->mikrotikRouter) {
        $assignedRouters = collect([$customer->mikrotikRouter]);
    }
    $assignedRouterIds = $assignedRouters->pluck('id')->map(fn ($id) => (int) $id)->all();

    if (empty($assignedRouterIds) && $customer->mikrotik_router_id) {
        $assignedRouterIds = [(int) $customer->mikrotik_router_id];
    }

    $servicePackage = $serviceSubscription?->package;
    $isActive = $customer->status === 'active';

    $routerTargetsExists = $customer->mikrotik_username || $customer->connection_id;
    if (empty($assignedRouterIds) && $assignedRouters->isNotEmpty()) {
        $assignedRouterIds = $assignedRouters->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    $daysLeftLabel = match (true) {
        $daysRemaining === null => 'No active validity found',
        $daysRemaining < 0 => 'Expired '.abs($daysRemaining).' day(s) ago',
        $daysRemaining === 0 => 'Last day',
        default => $daysRemaining.' day(s) remaining',
    };
    $validityTone = match (true) {
        $daysRemaining === null => 'neutral',
        $daysRemaining < 0 => 'danger',
        $daysRemaining <= 3 => 'warning',
        default => 'success',
    };

    $roleBadges = collect();
    if ($customer->is_customer) {
        $roleBadges->push(['label' => 'Customer', 'class' => 'active']);
    }
    if ($customer->is_reseller) {
        $roleBadges->push(['label' => 'Reseller', 'class' => 'overdue']);
    }
    if ($customer->is_vendor) {
        $roleBadges->push(['label' => 'Vendor', 'class' => 'pending']);
    }
@endphp

<style>
    .customer-shell {
        max-width: 1320px;
        margin: 0 auto;
    }
    .customer-hero {
        border-radius: 18px;
        padding: 24px;
        border: 1px solid #c7deff;
        background: linear-gradient(132deg, #0f2749 10%, #0d3d6a 42%, #14634e 96%);
        color: #fff;
        box-shadow: 0 14px 32px rgba(11, 37, 74, .22);
    }
    .customer-hero__head {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
    }
    .customer-hero__label {
        margin: 0 0 6px;
        color: #d3ecff;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: 12px;
        font-weight: 800;
    }
    .customer-hero__title {
        margin: 0;
        font-size: 34px;
        line-height: 1.12;
        letter-spacing: -0.02em;
    }
    .customer-hero__meta {
        margin: 8px 0 0;
        color: #d7e8ff;
    }
    .customer-status {
        margin-top: 12px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 7px 12px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .3);
        font-weight: 700;
    }
    .customer-status::before {
        content: "";
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #22c55e;
    }
    .customer-status.offline::before {
        background: #fda29b;
    }
    .hero-actions {
        margin-top: 16px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, max-content));
        gap: 10px;
        justify-content: end;
    }
    .hero-actions .btn {
        min-height: 44px;
        padding-inline: 15px;
    }
    .hero-actions .btn.light {
        color: #0f2749;
    }
    .hero-actions .btn--ghost {
        background: rgba(255,255,255,.24);
        color: #ecf4ff;
        border: 1px solid rgba(255,255,255,.22);
    }
    .customer-summary {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }
    .hero-kpi {
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,.22);
        background: rgba(10, 31, 63, .27);
        padding: 13px;
    }
    .hero-kpi__label {
        color: #d3e6ff;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .hero-kpi__value {
        margin-top: 6px;
        font-size: 23px;
        font-weight: 800;
        line-height: 1.2;
    }
    .hero-kpi__meta {
        margin-top: 5px;
        color: #c8ddfa;
        font-size: 12px;
        font-weight: 700;
    }
    .customer-grid {
        margin-top: 16px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr) minmax(0, 1fr);
        gap: 16px;
    }
    .customer-card {
        border-radius: 15px;
        padding: 18px;
        border: 1px solid #dce6f4;
        background: #fff;
        box-shadow: 0 7px 24px rgba(15, 23, 42, .08);
    }
    .customer-card__heading {
        margin: 0 0 14px;
        font-size: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .customer-card__heading::before {
        content: "";
        width: 5px;
        height: 22px;
        border-radius: 999px;
        background: #1d76c9;
    }
    .kv-grid {
        display: grid;
        grid-template-columns: 140px minmax(0, 1fr);
        gap: 11px 16px;
        align-items: start;
        margin: 0;
    }
    .kv-grid__label {
        font-size: 13px;
        color: #54627c;
        font-weight: 700;
        margin-top: 2px;
    }
    .kv-grid__value {
        background: #f8fafc;
        border: 1px solid #e2e9f2;
        border-radius: 8px;
        padding: 10px 11px;
        min-height: 40px;
        word-break: break-word;
    }
    .kv-grid__note {
        grid-column: 1 / -1;
        border-top: 1px dashed #d3deea;
        margin-top: 3px;
        padding-top: 10px;
    }
    .badge-row {
        margin-top: 2px;
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }
    .stat-pill {
        display: inline-flex;
        border-radius: 10px;
        border: 1px solid #dbe5f4;
        background: #f5f8fc;
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 700;
    }
    .stat-pill__big {
        font-size: 16px;
        font-weight: 800;
        margin-top: 3px;
    }
    .stat-pill.success {
        background: #ecfdf3;
        border-color: #a7f3d0;
        color: #027a48;
    }
    .stat-pill.warning {
        background: #fffaeb;
        border-color: #fedf89;
        color: #b54708;
    }
    .stat-pill.danger {
        background: #fffbfa;
        border-color: #f7c7c0;
        color: #b42318;
    }
    .stat-pill.neutral {
        background: #eff3f9;
        border-color: #d6dfeb;
        color: #344054;
    }
    .customer-routers {
        margin-top: 14px;
    }
    .mikrotik-grid {
        margin-top: 9px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .mikrotik-item {
        border: 1px solid #dbe5f4;
        border-radius: 9px;
        padding: 8px 10px;
        background: #fff;
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .mikrotik-item input {
        width: auto;
        margin-top: 3px;
    }
    .mikrotik-item strong {
        display: block;
        font-size: 14px;
    }
    .mikrotik-item small {
        display: block;
        margin-top: 2px;
        color: #667085;
    }
    .action-row {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .action-row .btn {
        min-height: 38px;
        width: auto;
    }
    .form-panel {
        margin-top: 14px;
        padding: 12px;
        border: 1px dashed #bfd4ee;
        border-radius: 10px;
        background: #f7fbff;
    }
    .form-panel__title {
        margin: 0 0 10px;
        font-weight: 700;
        color: #344054;
    }
    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .table-wrap {
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid #dce6f4;
        background: #fff;
        margin-top: 12px;
    }
    .customer-tabs {
        margin-top: 16px;
        border: 1px solid #dce6f4;
        border-radius: 15px;
        background: #fff;
        padding: 14px;
    }
    .tab-list {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .customer-tab {
        border: 1px solid #d9e3ee;
        border-bottom: 0;
        border-top-left-radius: 9px;
        border-top-right-radius: 9px;
        background: #f3f7fc;
        color: #334155;
        padding: 10px 12px;
        font-weight: 700;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
    }
    .customer-tab[aria-selected="true"] {
        background: #ffffff;
        border-color: #c8d8ef;
        color: #0f2749;
        border-bottom-color: #0f2749;
    }
    .customer-tab-panel {
        display: none;
        border-top: 1px solid #d9e3ee;
        margin-top: -1px;
        padding-top: 14px;
    }
    .customer-tab-panel.is-active {
        display: block;
    }
    .customer-tab-panel .card {
        border: 0;
        box-shadow: none;
        padding: 0;
    }
    .customer-extra {
        margin-top: 16px;
    }
    .details-stack {
        border: 1px solid #dce6f4;
        border-radius: 15px;
        background: #fff;
        margin-top: 16px;
        padding: 12px 16px;
    }
    .details-stack summary {
        cursor: pointer;
        font-weight: 700;
        padding: 8px 0;
        list-style: none;
    }
    .details-stack summary::-webkit-details-marker {
        display: none;
    }
    .details-stack + .details-stack {
        margin-top: 10px;
    }
    .details-stack .table-wrap {
        margin-top: 10px;
    }

    @media (max-width: 1120px) {
        .customer-grid {
            grid-template-columns: 1fr 1fr;
        }
        .customer-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .hero-actions {
            justify-content: start;
            grid-template-columns: repeat(2, minmax(150px, 1fr));
        }
    }
    @media (max-width: 820px) {
        .customer-grid {
            grid-template-columns: 1fr;
        }
        .customer-hero {
            padding: 18px;
        }
        .customer-hero__head {
            display: block;
        }
        .hero-actions {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .hero-actions .btn,
        .hero-actions .btn.light {
            width: 100%;
            justify-content: center;
            text-align: center;
        }
        .customer-summary {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .form-grid-2 {
            grid-template-columns: 1fr;
        }
        .mikrotik-grid {
            grid-template-columns: 1fr;
        }
        .customer-tab {
            width: 100%;
            text-align: left;
        }
        .customer-tab-panel {
            padding-top: 12px;
        }
        .customer-shell {
            padding: 0 2px;
        }
    }
    @media (max-width: 560px) {
        .customer-hero__title {
            font-size: 28px;
            line-height: 1.1;
        }
        .hero-kpi__value {
            font-size: 20px;
        }
        .customer-summary {
            grid-template-columns: 1fr;
        }
        .hero-actions {
            grid-template-columns: 1fr;
        }
        .customer-tabs {
            padding: 10px;
        }
        .customer-tab {
            min-height: 44px;
        }
    }
</style>

<div class="customer-shell">
    <section class="customer-hero">
        <div class="customer-hero__head">
            <div>
                <p class="customer-hero__label">Customer Profile</p>
                <h1 class="customer-hero__title">{{ $customer->name }}</h1>
                <p class="customer-hero__meta">
                    {{ $customer->connection_id ?: 'ID not assigned' }} • {{ $customer->phone ?: 'Phone not provided' }}
                </p>
                <div class="customer-status {{ $isActive ? '' : 'offline' }}">{{ $isActive ? 'Active' : 'Offline' }}</div>
            </div>
            <div class="hero-actions">
                <a class="btn" href="{{ route('customers.payments.create', $customer) }}">Quick Recharge</a>
                <a class="btn secondary" href="{{ route('customers.edit', $customer) }}">Edit Profile</a>
                <button class="btn btn--ghost" type="button" id="mikrotik-quick-sync" {{ ! $routerTargetsExists ? 'disabled' : '' }}>
                    MikroTik Sync
                </button>
                <a class="btn secondary" href="{{ route('customers.history', $customer) }}">Edit History</a>
                <a class="btn light" href="{{ route('customers.index') }}">All Parties</a>
            </div>
        </div>
        <div class="customer-summary">
            <div class="hero-kpi">
                <div class="hero-kpi__label">Service status</div>
                <div class="hero-kpi__value">{{ strtoupper($customer->status) }}</div>
                <div class="hero-kpi__meta">{{ $daysLeftLabel }}</div>
            </div>
            <div class="hero-kpi">
                <div class="hero-kpi__label">Validity until</div>
                <div class="hero-kpi__value">{{ $activeUntil?->format('d/m/Y') ?? 'Not set' }}</div>
                <div class="hero-kpi__meta">Expires in: {{ $daysLeftLabel }}</div>
            </div>
            <div class="hero-kpi">
                <div class="hero-kpi__label">Current due</div>
                <div class="hero-kpi__value">৳ {{ number_format($totalDue, 2) }}</div>
                <div class="hero-kpi__meta">From invoices</div>
            </div>
            <div class="hero-kpi">
                <div class="hero-kpi__label">Package</div>
                <div class="hero-kpi__value">
                    {{ $servicePackage ? $servicePackage->name : 'Not assigned' }}
                </div>
                <div class="hero-kpi__meta">
                    {{ $servicePackage ? '৳ '.number_format((float) $servicePackage->monthly_price, 2) : 'Set package first' }}
                </div>
            </div>
        </div>
    </section>

    <section class="customer-grid">
        <article class="customer-card">
            <h2 class="customer-card__heading">Personal Details</h2>
            <dl class="kv-grid">
                <dt class="kv-grid__label">Name</dt>
                <dd class="kv-grid__value">{{ $customer->name }}</dd>

                <dt class="kv-grid__label">Phone</dt>
                <dd class="kv-grid__value">
                    @if ($customer->phone)
                        <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
                    @else
                        Not provided
                    @endif
                </dd>

                <dt class="kv-grid__label">Email</dt>
                <dd class="kv-grid__value">
                    @if ($customer->email)
                        <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                    @else
                        Not provided
                    @endif
                </dd>

                <dt class="kv-grid__label">Connection ID</dt>
                <dd class="kv-grid__value">{{ $customer->connection_id ?: 'Not assigned' }}</dd>

                <dt class="kv-grid__label">MikroTik user</dt>
                <dd class="kv-grid__value">{{ $customer->mikrotik_username ?: 'Not assigned' }}</dd>

                <dt class="kv-grid__label">Role</dt>
                <dd class="kv-grid__value">
                    <div class="badge-row">
                        @foreach ($roleBadges as $badge)
                            <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        @endforeach
                        @if ($customer->never_suspend)
                            <span class="badge active">No auto suspend</span>
                        @endif
                    </div>
                </dd>

                <dt class="kv-grid__label">Address</dt>
                <dd class="kv-grid__value kv-grid__note">{{ $customer->address ?: 'Not provided' }}</dd>

                <dt class="kv-grid__label">Party note</dt>
                <dd class="kv-grid__value kv-grid__note">
                    {{ $customer->notes ?: 'No note' }}
                </dd>
                <dt class="kv-grid__label">MikroTik comment</dt>
                <dd class="kv-grid__value kv-grid__note">
                    {{ $customer->importedSecret?->router_comment ?: 'No comment' }}
                </dd>
            </dl>
        </article>

        <article class="customer-card">
            <h2 class="customer-card__heading">Billing &amp; Package Info</h2>
            <div class="stat-pill {{ $validityTone }}">
                <span>Validity status:</span>
                <strong class="stat-pill__big" style="margin-left:6px;">{{ $daysLeftLabel }}</strong>
            </div>
            <dl class="kv-grid" style="margin-top:10px;">
                <dt class="kv-grid__label">Current package</dt>
                <dd class="kv-grid__value">
                    @if ($servicePackage)
                        <strong>{{ $servicePackage->name }}</strong><br>
                        ৳ {{ number_format((float) $servicePackage->monthly_price, 2) }}
                        <div class="muted">Profile: {{ $servicePackage->mikrotik_profile ?: 'auto' }}</div>
                    @else
                        Not assigned
                    @endif
                </dd>

                <dt class="kv-grid__label">Validity until</dt>
                <dd class="kv-grid__value">
                    {{ $activeUntil?->format('d/m/Y') ?? 'Not set' }}
                    @if ($customer->hasActiveGracePeriod())
                        <div class="muted">Grace until: {{ $customer->grace_until?->format('d/m/Y') }}</div>
                    @endif
                </dd>

                <dt class="kv-grid__label">Advance balance</dt>
                <dd class="kv-grid__value">৳ {{ number_format((float) $customer->account_balance, 2) }}</dd>

                <dt class="kv-grid__label">Net payable</dt>
                <dd class="kv-grid__value">৳ {{ number_format($netBalance, 2) }}</dd>

                <dt class="kv-grid__label">Invoice count</dt>
                <dd class="kv-grid__value">{{ $customer->invoices->count() }}</dd>

                <dt class="kv-grid__label">Total invoiced</dt>
                <dd class="kv-grid__value">৳ {{ number_format($customer->invoices->sum('total'), 2) }}</dd>
            </dl>

            <div class="form-panel">
                <h3 class="form-panel__title">Force validity date</h3>
                <form method="post" action="{{ route('customers.service-validity.update', $customer) }}" class="form-grid-2">
                    @csrf
                    <div>
                        <label>New validity date</label>
                        <input type="date" name="service_valid_until" value="{{ old('service_valid_until', $customer->service_valid_until?->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label>Reason / note</label>
                        <input type="text" name="validity_note" value="{{ old('validity_note') }}" placeholder="Reason is required" required>
                    </div>
                    <div class="action-row" style="grid-column:1/-1">
                        <button class="btn secondary" type="submit">Save validity</button>
                    </div>
                </form>
            </div>

            @if ($servicePackage && (float) $customer->account_balance >= (float) ($servicePackage->monthly_price ?: 0))
                <div class="form-panel">
                    <h3 class="form-panel__title">Extend from advance balance</h3>
                    <form method="post" action="{{ route('customers.advance-renewal.store', $customer) }}">
                        @csrf
                        <div class="action-row">
                            <button class="btn" type="submit" @disabled($daysRemaining !== null && $daysRemaining >= 0)>
                                Renew 1 month from advance
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="form-panel">
                <h3 class="form-panel__title">Service control</h3>
                @if ($customer->status === 'active')
                    <form method="post" action="{{ route('customers.force-inactive', $customer) }}" class="form-grid-2" onsubmit="return confirm('Temporarily make this service inactive now?')">
                        @csrf
                        <div style="grid-column:1/-1">
                            <label>Reason / note</label>
                            <input type="text" name="inactive_note" value="{{ old('inactive_note') }}" placeholder="Reason is required" required>
                        </div>
                        <div class="action-row" style="grid-column:1/-1">
                            <button class="btn danger" type="submit">Temporary inactive</button>
                        </div>
                    </form>
                @else
                    <form method="post" action="{{ route('customers.force-active', $customer) }}" class="form-grid-2" onsubmit="return confirm('Temporarily make this service active now?')">
                        @csrf
                        <div style="grid-column:1/-1">
                            <label>Reason / note</label>
                            <input type="text" name="active_note" value="{{ old('active_note') }}" placeholder="Reason is required" required>
                        </div>
                        <div class="action-row" style="grid-column:1/-1">
                            <button class="btn secondary" type="submit">Temporary active</button>
                        </div>
                    </form>
                @endif
            </div>
        </article>

        <article class="customer-card">
            <h2 class="customer-card__heading">Network &amp; MikroTik</h2>
            <dl class="kv-grid">
                <dt class="kv-grid__label">Routers assigned</dt>
                <dd class="kv-grid__value">
                    @if ($assignedRouters->isNotEmpty())
                        {{ $assignedRouters->pluck('name')->join(', ') }}
                    @else
                        Not assigned
                    @endif
                </dd>

                <dt class="kv-grid__label">IP assignment</dt>
                <dd class="kv-grid__value">
                    @if ($customer->use_fixed_ip)
                        Fixed: {{ $customer->fixed_ip_address ?: 'Not set' }}
                    @else
                        Dynamic: {{ $customer->last_connected_ip ?: $customer->learned_ip_address ?: 'Not learned yet' }}
                    @endif
                </dd>

                <dt class="kv-grid__label">MAC</dt>
                <dd class="kv-grid__value">{{ $customer->last_connected_mac ?: 'Not learned yet' }}</dd>

                <dt class="kv-grid__label">Last connected at</dt>
                <dd class="kv-grid__value">{{ $customer->last_connected_at?->format('d/m/Y H:i') ?? 'Not learned yet' }}</dd>

                <dt class="kv-grid__label">OLT / ONU signal</dt>
                <dd class="kv-grid__value">Not available in party profile (track via OLT ONUs)</dd>

                <dt class="kv-grid__label">Sync status</dt>
                <dd class="kv-grid__value">
                    @if ($customer->last_connected_at)
                        <span class="stat-pill success">Last seen {{ $customer->last_connected_at->diffForHumans() }}</span>
                    @else
                        <span class="stat-pill neutral">Not synced yet</span>
                    @endif
                </dd>
            </dl>

            <div class="customer-routers">
                <h3 class="form-panel__title" style="margin:0 0 8px;">MikroTik targets</h3>
                <p class="muted">Selecting routers will sync PPPoE user to each selected MikroTik router on save.</p>
                <form method="post" action="{{ route('customers.mikrotik-targets.update', $customer) }}" id="mikrotik-target-form">
                    @csrf
                    <div class="mikrotik-grid">
                        @foreach($routers as $router)
                            <label class="mikrotik-item">
                                <input
                                    type="checkbox"
                                    name="mikrotik_router_ids[]"
                                    value="{{ $router->id }}"
                                    @checked(in_array($router->id, old('mikrotik_router_ids', $assignedRouterIds), true))
                                >
                                <span>
                                    <strong>{{ $router->name }}</strong>
                                    <small>{{ $router->ip_address }}:{{ $router->api_port }} • {{ ucfirst($router->status) }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="action-row">
                        @if ($routerTargetsExists && $routers->isNotEmpty())
                            <button class="btn secondary" type="submit">Save &amp; Sync targets</button>
                        @elseif ($customer->connection_id || $customer->mikrotik_username)
                            <button class="btn secondary" type="submit" disabled>No router available</button>
                        @else
                            <button class="btn secondary" type="button" disabled>Set Connection ID first</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="form-panel" style="margin-top:10px;">
                <h3 class="form-panel__title">Reseller</h3>
                <div class="kv-grid">
                    <dt class="kv-grid__label">Assigned reseller</dt>
                    <dd class="kv-grid__value">
                        @if ($customer->reseller)
                            {{ $customer->reseller->name }} • {{ $customer->reseller->phone }}
                        @else
                            Direct / no reseller
                        @endif
                    </dd>
                    @if ($customer->reseller)
                        <dt class="kv-grid__label">Commission</dt>
                        <dd class="kv-grid__value">{{ number_format((float) $customer->reseller->reseller_commission_percent, 2) }}%</dd>
                    @endif
                </div>
            </div>
        </article>
    </section>

    <section class="customer-tabs">
        <div class="tab-list" role="tablist" aria-label="Customer history tabs">
            <button class="customer-tab" type="button" data-tab="invoices" role="tab" aria-selected="true">
                Invoices ({{ $customer->invoices->count() }})
            </button>
            <button class="customer-tab" type="button" data-tab="payments" role="tab" aria-selected="false">
                Advance Balance History ({{ $customer->balanceTransactions->count() }})
            </button>
            <button class="customer-tab" type="button" data-tab="tickets" role="tab" aria-selected="false">
                Support Tickets ({{ $customer->tickets->count() }})
            </button>
        </div>

        <div class="customer-tab-panel is-active" id="tab-panel-invoices" role="tabpanel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Month</th>
                            <th>Total</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
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
                                <td>৳ {{ number_format($invoice->total, 2) }}</td>
                                <td>৳ {{ number_format($invoice->due_amount, 2) }}</td>
                                <td><span class="badge {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></td>
                                <td>{{ $invoice->created_at?->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No invoices yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="customer-tab-panel" id="tab-panel-payments" role="tabpanel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Reference</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->balanceTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date?->format('d/m/Y') ?? '-' }}</td>
                                <td><span class="badge {{ $transaction->direction === 'credit' ? 'active' : 'due' }}">{{ ucfirst($transaction->direction) }}</span></td>
                                <td>৳ {{ number_format($transaction->amount, 2) }}</td>
                                <td>৳ {{ number_format($transaction->balance_after, 2) }}</td>
                                <td>{{ $transaction->reference ?? 'N/A' }}</td>
                                <td>{{ $transaction->note ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No advance balance history yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="customer-tab-panel" id="tab-panel-tickets" role="tabpanel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Technician</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->tickets as $ticket)
                            <tr>
                                <td>{{ $ticket->subject }}</td>
                                <td>{{ $ticket->priority }}</td>
                                <td><span class="badge pending">{{ ucfirst($ticket->status) }}</span></td>
                                <td>{{ $ticket->technician?->name ?: 'Unassigned' }}</td>
                                <td>{{ $ticket->created_at?->format('d/m/Y') ?? '-' }}</td>
                                <td><a class="btn light" href="{{ route('tickets.show', $ticket) }}">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No support tickets yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if ($customer->is_reseller)
        <section class="details-stack">
            <details>
                <summary>Reseller details</summary>
                <div class="customer-card" style="margin-top:10px;">
                    <div class="customer-routers">
                        <div class="kv-grid">
                            <div class="kv-grid__label">Wallet balance</div>
                            <div class="kv-grid__value">৳ {{ number_format((float) $customer->account_balance, 2) }}</div>
                            <div class="kv-grid__label">Daily payment limit</div>
                            <div class="kv-grid__value">
                                {{ $customer->reseller_daily_payment_limit === null ? 'Unlimited' : '৳ '.number_format((float) $customer->reseller_daily_payment_limit, 2) }}
                            </div>
                            <div class="kv-grid__label">Commission</div>
                            <div class="kv-grid__value">{{ number_format((float) $customer->reseller_commission_percent, 2) }}%</div>
                            <div class="kv-grid__label">Assigned parties</div>
                            <div class="kv-grid__value">{{ $customer->resellerCustomers->count() }}</div>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Party</th><th>Connection ID</th><th>Phone</th><th>Status</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($customer->resellerCustomers as $resellerCustomer)
                                        <tr>
                                            <td>{{ $resellerCustomer->name }}</td>
                                            <td>{{ $resellerCustomer->connection_id ?: 'N/A' }}</td>
                                            <td>{{ $resellerCustomer->phone ?: 'Not provided' }}</td>
                                            <td><span class="badge {{ $resellerCustomer->status }}">{{ ucfirst($resellerCustomer->status) }}</span></td>
                                            <td><a class="btn light" href="{{ route('customers.show', $resellerCustomer) }}">View</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5">No party is assigned to this reseller.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </details>
        </section>
    @endif

    <section class="details-stack">
        <details>
            <summary>Assets &amp; Warranty</summary>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Product</th><th>Serial</th><th>Invoice</th><th>Sold Date</th><th>Warranty</th><th>Status</th><th>Action</th></tr>
                    </thead>
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
            </div>
        </details>
    </section>

    @if ($customer->commissionHistories->isNotEmpty())
        <section class="details-stack">
            <details>
                <summary>Commission change history</summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>#</th><th>Changed At</th><th>Previous</th><th>New</th><th>Changed By</th><th>Note</th></tr></thead>
                        <tbody>
                            @forelse($customer->commissionHistories as $history)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $history->changed_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $history->old_percent === null ? 'Initial' : number_format((float) $history->old_percent, 2).'%' }}</td>
                                    <td>{{ number_format((float) $history->new_percent, 2) }}%</td>
                                    <td>{{ $history->changedByUser?->name ?? 'System' }}</td>
                                    <td>{{ $history->note ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No commission changes yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </details>
        </section>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.customer-tab');
    const panels = document.querySelectorAll('.customer-tab-panel');

    const activateTab = function (tabId) {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.tab === tabId;
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.id === 'tab-panel-' + tabId);
        });
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', function () {
            activateTab(this.dataset.tab);
        });
    });

    const quickSyncButton = document.getElementById('mikrotik-quick-sync');
    const targetForm = document.getElementById('mikrotik-target-form');
    if (quickSyncButton && targetForm) {
        quickSyncButton.addEventListener('click', function () {
            const selected = targetForm.querySelectorAll('input[name=\"mikrotik_router_ids[]\"]:checked').length;
            if (! selected) {
                alert('Please select at least one MikroTik target first.');
                return;
            }
            targetForm.requestSubmit();
        });
    }
});
</script>
@endsection
