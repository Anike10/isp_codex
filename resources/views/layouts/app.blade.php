<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=us-20260718">
    <title>{{ $title ?? ($appOrganization?->name ?? config('app.name')) }}</title>
    <style>
        :root { color-scheme: light; --ink:#172033; --muted:#667085; --line:#d8dee9; --bg:#f4f7fb; --panel:#fff; --brand:#116149; --accent:#1d76c9; --warn:#b45309; --danger:#b42318; --zebra:#edf4f8; --zebra-soft:#f7fafc; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, sans-serif; color:var(--ink); background:var(--bg); }
        a { color:inherit; text-decoration:none; }
        .shell { min-height:100vh; }
        .app-header { position:sticky; top:0; z-index:50; background:#14213d; color:white; border-bottom:1px solid rgba(255,255,255,.12); box-shadow:0 8px 24px rgba(15, 23, 42, .16); }
        .header-inner { max-width:1440px; margin:0 auto; padding:12px 20px; display:grid; grid-template-columns:auto auto minmax(0, 1fr) auto; align-items:center; gap:14px; }
        .brand { font-size:20px; font-weight:700; white-space:nowrap; letter-spacing:0; }
        .nav-toggle { display:none; width:38px; height:38px; border:1px solid rgba(255,255,255,.18); border-radius:6px; background:rgba(255,255,255,.08); color:white; cursor:pointer; align-items:center; justify-content:center; }
        .nav-toggle-lines, .nav-toggle-lines::before, .nav-toggle-lines::after { display:block; width:18px; height:2px; border-radius:999px; background:currentColor; content:""; transition:transform .18s ease, opacity .18s ease; }
        .nav-toggle-lines { position:relative; }
        .nav-toggle-lines::before, .nav-toggle-lines::after { position:absolute; left:0; }
        .nav-toggle-lines::before { top:-6px; }
        .nav-toggle-lines::after { top:6px; }
        .nav-toggle[aria-expanded="true"] .nav-toggle-lines { transform:rotate(45deg); }
        .nav-toggle[aria-expanded="true"] .nav-toggle-lines::before { transform:translateY(6px) rotate(90deg); }
        .nav-toggle[aria-expanded="true"] .nav-toggle-lines::after { opacity:0; }
        .nav { grid-column:3; display:flex; gap:6px; align-items:center; flex-wrap:wrap; flex:1; padding:2px 0; }
        .nav a, .nav summary { color:#dbe7ff; padding:9px 11px; border-radius:6px; white-space:nowrap; font-size:14px; cursor:pointer; }
        .nav a:hover, .nav summary:hover, .nav details[open] summary { background:rgba(255,255,255,.1); color:white; }
        .nav summary { list-style:none; user-select:none; }
        .nav summary::-webkit-details-marker { display:none; }
        .nav summary::after { content:""; display:inline-block; width:7px; height:7px; margin-left:8px; border-right:2px solid currentColor; border-bottom:2px solid currentColor; transform:translateY(-2px) rotate(45deg); }
        .nav details[open] summary::after { transform:translateY(2px) rotate(225deg); }
        .nav-group { position:relative; }
        .nav-menu { display:none; position:absolute; top:calc(100% + 6px); left:0; z-index:80; min-width:190px; padding:6px; background:white; color:var(--ink); border:1px solid var(--line); border-radius:8px; box-shadow:0 16px 34px rgba(15, 23, 42, .18); }
        .nav details[open] .nav-menu { display:grid; gap:2px; }
        .nav-menu a { color:var(--ink); display:block; padding:9px 10px; border-radius:6px; }
        .nav-menu a:hover { background:#eef4fb; color:var(--ink); }
        .user-session { grid-column:4; display:flex; align-items:center; gap:8px; min-width:0; }
        .current-user { display:flex; flex-direction:column; min-width:0; padding:6px 9px; border:1px solid rgba(255,255,255,.16); border-radius:6px; background:rgba(255,255,255,.08); line-height:1.15; }
        .current-user-label { color:#aebfdd; font-size:10px; text-transform:uppercase; letter-spacing:.04em; }
        .current-user-name { max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:white; font-size:13px; }
        .logout-form { margin:0; }
        .logout-form .btn { min-height:34px; padding:8px 12px; white-space:nowrap; }
        .main { max-width:1440px; margin:0 auto; padding:24px 20px 34px; }
        .main.olt-onus-wide { max-width:none; margin-left:auto; margin-right:auto; padding-left:20px; padding-right:20px; }
        .topbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:20px; }
        h1 { margin:0; font-size:28px; }
        h2 { margin:0 0 14px; font-size:20px; }
        .grid { display:grid; gap:16px; }
        .stats { grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); }
        .two { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:8px; padding:18px; }
        .stat strong { display:block; font-size:26px; margin-top:8px; }
        .muted { color:var(--muted); }
        .actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .action-group { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
        .btn { border:0; border-radius:6px; background:var(--brand); color:white; padding:10px 14px; cursor:pointer; font-weight:700; display:inline-flex; align-items:center; min-height:38px; }
        .btn.secondary { background:var(--accent); }
        .btn.light { background:#e8eef7; color:var(--ink); }
        .btn.danger { background:var(--danger); }
        .action-menu { position:relative; display:inline-block; }
        .action-menu summary { list-style:none; user-select:none; }
        .action-menu summary::-webkit-details-marker { display:none; }
        .action-menu-panel { position:absolute; right:0; top:calc(100% + 6px); z-index:35; min-width:190px; display:grid; gap:4px; padding:6px; background:#fff; border:1px solid var(--line); border-radius:8px; box-shadow:0 16px 34px rgba(15, 23, 42, .18); }
        .action-menu-panel .btn, .action-menu-panel form, .action-menu-panel button { width:100%; justify-content:flex-start; }
        .action-menu-panel form { margin:0; }
        table { width:100%; border-collapse:collapse; background:white; border:1px solid var(--line); border-radius:8px; overflow:hidden; }
        tr[data-href] { cursor:pointer; }
        tbody tr:nth-child(even) td { background:var(--zebra); }
        tr[data-href]:hover td { background:#f6faf8; }
        tr.invoice-row-due td { background:#fffaf0; }
        tr.invoice-row-overdue td { background:#fff4f4; }
        tr.invoice-row-paid td { background:#f3fbf6; }
        tbody tr.invoice-row-due:nth-child(even) td { background:#fff7e8; }
        tbody tr.invoice-row-overdue:nth-child(even) td { background:#ffecec; }
        tbody tr.invoice-row-paid:nth-child(even) td { background:#edf8f2; }
        tr.customer-row-special td { background:#eff8ff; }
        tr.customer-row-special:nth-child(even) td { background:#e2f0ff; }
        tr.customer-row-special:hover td { background:#d7eaff; }
        tr.customer-row-overdue td { background:#fff3ec; }
        tr.customer-row-overdue:nth-child(even) td { background:#ffe9dc; }
        tr.customer-row-overdue:hover td { background:#ffdfcb; }
        .badge.special { background:#1d76c9; color:#ecfeff; }
        th, td { padding:12px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top; }
        th { background:#edf2f7; font-size:13px; text-transform:uppercase; color:#475467; }
        tr:last-child td { border-bottom:0; }
        .detail-list .detail-row:nth-child(even) { background:var(--zebra-soft); }
        label { display:block; font-weight:700; margin-bottom:6px; }
        input, select, textarea { width:100%; border:1px solid var(--line); border-radius:6px; padding:10px; font:inherit; background:white; }
        textarea { min-height:100px; resize:vertical; }
        .form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
        .full { grid-column:1 / -1; }
        .alert { padding:12px 14px; border-radius:6px; margin-bottom:16px; }
        .alert.success { background:#e7f7ef; color:#05603a; }
        .alert.warning { background:#fffaeb; color:var(--warn); }
        .alert.error { background:#fff0f0; color:var(--danger); }
        .badge { display:inline-block; padding:4px 8px; border-radius:999px; background:#eef2ff; font-size:12px; font-weight:700; }
        .badge.due { background:#fff7ed; color:var(--warn); }
        .badge.unpaid, .badge.draft { background:#fff7ed; color:var(--warn); }
        .badge.partial { background:#eff6ff; color:#175cd3; }
        .badge.final { background:#ecfdf3; color:#027a48; }
        .badge.overdue { background:#fff0f0; color:var(--danger); }
        .badge.paid, .badge.active { background:#ecfdf3; color:#027a48; }
        .badge.returned { background:#eef2ff; color:#3730a3; }
        .badge.processed, .badge.balance { background:#ecfdf3; color:#027a48; }
        .badge.pending { background:#fff7ed; color:var(--warn); }
        .badge.duplicate { background:#eef2ff; color:#175cd3; }
        .badge.failed { background:#fff0f0; color:var(--danger); }
        .badge.online { background:#ecfdf3; color:#027a48; }
        .badge.offline { background:#fff0f0; color:var(--danger); }
        .olt-progress-track { position:relative; height:14px; overflow:hidden; border-radius:999px; background:#dfe7f1; }
        .olt-progress-fill { height:100%; min-width:2%; border-radius:999px; background:linear-gradient(90deg,var(--brand),#35a77b,var(--brand)); background-size:200% 100%; transition:width .5s ease; animation:oltProgressMove 1.2s linear infinite; }
        @keyframes oltProgressMove { from { background-position:200% 0; } to { background-position:0 0; } }
        .badge.checking { background:#eef2ff; color:#175cd3; }
        .badge.inactive { background:#f2f4f7; color:#475467; }
        .badge.open, .badge.processing { background:#eff6ff; color:#175cd3; }
        .badge.low { background:#fff1f3; color:#c01048; }
        .connection-cell { min-width:132px; white-space:nowrap; }
        .router-connection, .router-ping { min-width:104px; text-align:center; }
        .router-checked-at { min-height:17px; white-space:nowrap; }
        .page-timing { position:fixed; right:12px; bottom:10px; z-index:120; padding:6px 9px; border-radius:6px; background:rgba(20,33,61,.9); color:white; font-size:12px; box-shadow:0 8px 20px rgba(15,23,42,.18); }
        .app-footer { max-width:1440px; margin:0 auto; padding:0 20px 18px; color:var(--muted); font-size:13px; text-align:center; }
        .per-page-form { justify-content:flex-end; margin:0 0 12px; }
        .per-page-label { margin:0; display:flex; align-items:center; gap:8px; font-weight:700; }
        .per-page-select { width:auto; min-width:90px; }
        .pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .pagination-summary { color:var(--muted); font-size:14px; }
        .pagination-summary.pagination-summary-top { margin:16px 0 8px; font-weight:700; }
        .pagination-links { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .page-link { min-width:38px; min-height:38px; padding:9px 12px; border:1px solid var(--line); border-radius:6px; background:white; color:var(--ink); display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; }
        .page-link:hover { background:#eef4fb; }
        .page-link.active { background:var(--brand); border-color:var(--brand); color:white; }
        .page-link.disabled, .page-link.dots { color:#98a2b3; background:#f8fafc; cursor:not-allowed; }
        @media (max-width: 980px) {
            .stats, .two, .form-grid { grid-template-columns:1fr; }
            .header-inner { grid-template-columns:minmax(0, 1fr) auto auto; gap:8px; padding:10px 12px; }
            .brand { font-size:18px; min-width:0; overflow:hidden; text-overflow:ellipsis; }
            .nav-toggle { display:inline-flex; grid-column:3; grid-row:1; }
            .user-session { grid-column:2; grid-row:1; }
            .nav { display:none; grid-column:1 / -1; grid-row:2; width:100%; padding:8px; gap:6px; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1); border-radius:8px; box-shadow:inset 0 1px 0 rgba(255,255,255,.08); }
            .nav.is-open { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); align-items:stretch; }
            .nav a, .nav summary { display:flex; align-items:center; justify-content:space-between; min-height:42px; white-space:normal; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.08); line-height:1.2; }
            .nav details { min-width:0; }
            .nav details[open] { grid-column:1 / -1; }
            .nav-menu { position:static; min-width:100%; margin-top:6px; background:#fff; }
            .nav details[open] .nav-menu { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:6px; }
            .main { padding:16px 12px 28px; }
            .topbar { align-items:flex-start; flex-direction:column; }
            .actions { width:100%; }
            .actions .btn, .actions form, .actions input, .actions select { max-width:100%; }
            table { display:block; overflow-x:auto; -webkit-overflow-scrolling:touch; border-radius:7px; box-shadow:0 1px 0 rgba(15,23,42,.04); }
            th, td { padding:10px; }
            h1 { font-size:24px; }
            .stat strong { font-size:22px; }
            .card { padding:14px; }
        }
        @media print {
            table, th, td, .detail-list .detail-row {
                -webkit-print-color-adjust:exact;
                print-color-adjust:exact;
            }
        }
        @media (max-width: 560px) {
            .app-header { position:static; }
            .header-inner { padding:9px 10px; }
            .brand { font-size:17px; }
            .nav-toggle { width:36px; height:36px; }
            .nav { margin-top:2px; padding:7px; border-radius:7px; }
            .nav.is-open { grid-template-columns:1fr; }
            .nav a, .nav summary { font-size:13px; padding:9px 10px; border-radius:6px; }
            .nav details[open] .nav-menu { grid-template-columns:1fr; }
            .nav-menu a { border-radius:6px; font-size:13px; min-height:38px; color:var(--ink); }
            .btn { width:100%; justify-content:center; }
            .user-session { margin-left:auto; gap:5px; }
            .current-user { padding:5px 7px; }
            .current-user-label { display:none; }
            .current-user-name { max-width:90px; font-size:12px; }
            .logout-form { margin:0; }
            .logout-form .btn { width:auto; min-height:32px; padding:7px 10px; font-size:12px; }
            input, select, textarea { font-size:16px; }
            .main { padding:12px 10px 24px; }
            .topbar { gap:10px; margin-bottom:12px; }
            h1 { font-size:22px; line-height:1.2; }
            h2 { font-size:18px; }
            .muted { font-size:13px; line-height:1.4; }
            .grid { gap:10px; }
            .card { padding:12px; border-radius:7px; }
            .stats { gap:10px; }
            .stats { grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .stat strong { font-size:20px; margin-top:4px; }
            .actions { gap:8px; }
            .actions > *, .actions .btn, .actions form { width:100%; }
            .btn { min-height:36px; padding:9px 11px; font-size:13px; }
            th, td { padding:9px 8px; font-size:13px; }
            th { font-size:11px; }
            label { font-size:13px; }
            .per-page-form { justify-content:flex-start; }
            .per-page-label { width:100%; justify-content:space-between; }
            .per-page-select { width:120px; min-width:120px; }
            .pagination-wrap { align-items:stretch; gap:10px; }
            .pagination-summary { width:100%; text-align:center; font-size:13px; }
            .pagination-links { width:100%; justify-content:center; gap:5px; }
            .page-link { min-width:34px; min-height:34px; padding:8px 10px; font-size:13px; }
        }
        @media (max-width: 360px) {
            .stats { grid-template-columns:1fr; }
            .nav a, .nav summary { font-size:11px; padding:7px 8px; }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/gorgeous-theme.css') }}?v=20260719-17">
    <link rel="stylesheet" href="{{ asset('css/page-help.css') }}?v=20260811-1">
</head>
<body class="app-theme">
<div class="shell">
    <header class="app-header">
    <div class="header-inner">
        <div class="brand">{{ $appOrganization?->name ?? config('app.name') }}</div>
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-controls="app-nav" aria-expanded="false">
            <span class="nav-toggle-lines"></span>
        </button>
        <nav class="nav" id="app-nav">
            @php
                $currentUser = auth()->user();
                $canMenu = fn (string $key): bool => (bool) $currentUser?->canAccessMenu($key);
                $canManageNetwork = collect(['packages', 'mikrotik_routers', 'ip_pools', 'network_map', 'olt_onus', 'onu_deny_list', 'onu_auto_discovery', 'olt_protocol_profiles', 'unmanaged_router_users'])->contains($canMenu);
                $canManageBilling = collect(['parties', 'resellers', 'invoices', 'create_invoice', 'sale_returns', 'quotations', 'create_quotation', 'payment_note_default', 'organizations', 'print_history', 'payments', 'bkash_sms', 'payment_accounts', 'accounting_ledger', 'concession_reports', 'employees', 'expenses'])->contains($canMenu);
                $canManageWarranty = collect(['warranty_claims', 'new_warranty_claim'])->contains($canMenu);
                $canManageTroubleshoot = collect(['troubleshoot_webhook', 'troubleshoot_frequent_disconnects', 'troubleshoot_analytics'])->contains($canMenu);
                $canManageAdmin = collect(['users', 'roles', 'database_backup'])->contains($canMenu)
                    || (bool) $currentUser?->isSuperAdmin();
                $canManageFleet = collect(['fleet_vehicles', 'fleet_add_vehicle', 'fleet_maintenance_schedules', 'fleet_log_maintenance', 'fleet_settings', 'fleet_reports', 'fleet_expense_report', 'fleet_maintenance_report', 'fleet_due_report', 'fleet_duty_history'])->contains($canMenu)
                    && Route::has('fleet.index');
            @endphp
            @if ($canMenu('dashboard'))
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @endif
            @if ($canMenu('reseller_portal') && $currentUser?->reseller_id)
                <a href="{{ route('reseller.dashboard') }}">Reseller Portal</a>
            @endif

            @if ($canManageNetwork)
                <details class="nav-group">
                    <summary>Network</summary>
                    <div class="nav-menu">
                        @if ($canMenu('packages'))
                            <a href="{{ route('packages.index') }}">Packages</a>
                        @endif
                        @if ($canMenu('mikrotik_routers'))
                            <a href="{{ route('mikrotik-routers.index') }}">MikroTik Routers</a>
                        @endif
                        @if ($canMenu('ip_pools') && Route::has('ip-pools.index'))
                            <a href="{{ route('ip-pools.index') }}">IP Pools</a>
                        @endif
                        @if ($canMenu('network_map'))
                            <a href="{{ route('network-map.index') }}">FTTX Network Map</a>
                            <a href="{{ route('network-map.party-locations.index') }}">Party Location Manager</a>
                        @endif
                        @if ($canMenu('olt_onus'))
                            <a href="{{ route('olt-onus.index') }}">OLT ONUs</a>
                        @endif
                        @if ($canMenu('onu_deny_list'))
                            <a href="{{ route('olt-onus.deny-list') }}">ONU Deny List</a>
                        @endif
                        @if ($canMenu('onu_auto_discovery'))
                            <a href="{{ route('olt-onus.auto-discovery') }}">Auto Discovery List</a>
                        @endif
                        @if ($canMenu('olt_protocol_profiles'))
                            <a href="{{ route('olt-onus.protocol-profiles.index') }}">OLT Protocol/Profile</a>
                        @endif
                        @if ($canMenu('unmanaged_router_users'))
                            <a href="{{ route('router-users.index') }}">Router Users Not In App</a>
                        @endif
                    </div>
                </details>
            @endif

            @if ($canManageTroubleshoot)
                <details class="nav-group">
                    <summary>Troubleshoot</summary>
                    <div class="nav-menu">
                        @if ($canMenu('troubleshoot_webhook'))
                            <a href="{{ route('troubleshoot.webhook.edit') }}">Webhook Settings</a>
                        @endif
                        @if ($canMenu('troubleshoot_frequent_disconnects'))
                            <a href="{{ route('troubleshoot.frequent-disconnects') }}">Frequent Disconnects</a>
                        @endif
                        @if ($canMenu('troubleshoot_analytics'))
                            <a href="{{ route('troubleshoot.analytics') }}">Connection Analytics</a>
                        @endif
                    </div>
                </details>
            @endif

            @if ($canManageBilling)
                <details class="nav-group">
                    <summary>Billing</summary>
                    <div class="nav-menu">
                        @if ($canMenu('parties'))
                            <a href="{{ route('customers.index') }}">Parties</a>
                            <a href="{{ route('customers.deleted') }}">Deleted Parties</a>
                        @endif
                        @if ($canMenu('resellers'))
                            <a href="{{ route('resellers.index') }}">Resellers</a>
                        @endif
                        @if ($canMenu('invoices'))
                            <a href="{{ route('invoices.index') }}">Invoices</a>
                        @endif
                        @if ($canMenu('create_invoice'))
                            <a href="{{ route('invoices.create') }}">Create Invoice</a>
                        @endif
                        @if ($canMenu('sale_returns') && Route::has('sale-returns.index'))
                            <a href="{{ route('sale-returns.index') }}">Sale Returns</a>
                        @endif
                        @if ($canMenu('quotations'))
                            <a href="{{ route('quotations.index') }}">Quotations</a>
                        @endif
                        @if ($canMenu('create_quotation'))
                            <a href="{{ route('quotations.create') }}">Create Quotation</a>
                        @endif
                        @if ($canMenu('payment_note_default'))
                            <a href="{{ route('organizations.edit', 1) }}">Payment Note Default</a>
                        @endif
                        @if ($canMenu('organizations'))
                            <a href="{{ route('organizations.index') }}">Organizations</a>
                        @endif
                        @if ($canMenu('print_history'))
                            <a href="{{ route('print-logs.index') }}">Print History</a>
                        @endif
                        @if ($canMenu('payments'))
                            <a href="{{ route('payments.index') }}">Payments</a>
                        @endif
                        @if ($canMenu('bkash_sms'))
                            <a href="{{ route('bkash-sms-payments.index') }}">bKash SMS</a>
                        @endif
                        @if ($canMenu('payment_accounts'))
                            <a href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
                        @endif
                        @if ($canMenu('accounting_ledger'))
                            <a href="{{ route('accounting.ledger') }}">Accounting Ledger</a>
                        @endif
                        @if ($canMenu('concession_reports'))
                            <a href="{{ route('concession-reports.index') }}">Concession Reports</a>
                        @endif
                        @if ($canMenu('employees'))
                            <a href="{{ route('employees.index') }}">Employees</a>
                        @endif
                        @if ($canMenu('expenses'))
                            <a href="{{ route('expenses.index') }}">Salary & Expenses</a>
                        @endif
                    </div>
                </details>
            @endif

            @if ($canMenu('tickets'))
                <a href="{{ route('tickets.index') }}">Tickets</a>
            @endif
            @if (collect(['products', 'in_house_use', 'employee_asset_report', 'returned_used_stock', 'in_house_history', 'warehouses', 'stock_transfer', 'stock_history', 'product_categories', 'purchase_bills'])->contains($canMenu))
                <details class="nav-group">
                    <summary>Inventory</summary>
                    <div class="nav-menu">
                        @if ($canMenu('products'))
                            <a href="{{ route('products.index') }}">Products</a>
                        @endif
                        @if ($canMenu('in_house_use') && Route::has('in-house-use.index'))
                            <a href="{{ route('in-house-use.index') }}">In-house Use</a>
                        @endif
                        @if ($canMenu('employee_asset_report') && Route::has('in-house-use.report.employees'))
                            <a href="{{ route('in-house-use.report.employees') }}">Employee Asset Report</a>
                        @endif
                        @if ($canMenu('returned_used_stock') && Route::has('in-house-use.report.used-stock'))
                            <a href="{{ route('in-house-use.report.used-stock') }}">Returned Used Stock</a>
                        @endif
                        @if ($canMenu('in_house_history') && Route::has('in-house-use.report.history'))
                            <a href="{{ route('in-house-use.report.history') }}">In-house History</a>
                        @endif
                        @if ($canMenu('warehouses')) <a href="{{ route('warehouses.index') }}">Warehouses</a> @endif
                        @if ($canMenu('stock_transfer')) <a href="{{ route('warehouse-transfers.create') }}">Stock Transfer</a> @endif
                        @if ($canMenu('stock_history')) <a href="{{ route('warehouse-movements.index') }}">Stock History</a> @endif
                        @if ($canMenu('product_categories')) <a href="{{ route('product-categories.index') }}">Product Categories</a> @endif
                        @if ($canMenu('purchase_bills')) <a href="{{ route('purchase-bills.index') }}">Purchase Bills</a> @endif
                    </div>
                </details>
            @endif

            @if ($canManageWarranty)
                <details class="nav-group">
                    <summary>Warranty</summary>
                    <div class="nav-menu">
                        @if ($canMenu('warranty_claims'))
                            <a href="{{ route('warranty-claims.index') }}">Warranty Claims</a>
                        @endif
                        @if ($canMenu('new_warranty_claim'))
                            <a href="{{ route('warranty-claims.create') }}">New Claim</a>
                        @endif
                    </div>
                </details>
            @endif
            @if ($canMenu('expenses'))
                <a href="{{ route('expenses.index') }}">Expenses</a>
            @endif
            @if ($canManageFleet)
                <details class="nav-group">
                    <summary>Fleet</summary>
                    <div class="nav-menu">
                        @if ($canMenu('fleet_vehicles')) <a href="{{ route('fleet.index') }}">Vehicles</a> @endif
                        @if ($canMenu('fleet_add_vehicle')) <a href="{{ route('fleet.create') }}">Add Vehicle</a> @endif
                        @if ($canMenu('fleet_maintenance_schedules')) <a href="{{ route('fleet.maintenance.schedules') }}">Maintenance Schedules</a> @endif
                        @if ($canMenu('fleet_log_maintenance')) <a href="{{ route('fleet.maintenance.logs.create') }}">Log Repair / Maintenance</a> @endif
                        @if ($canMenu('fleet_settings')) <a href="{{ route('fleet.settings') }}">Fleet Settings</a> @endif
                        @if ($canMenu('fleet_reports')) <a href="{{ route('fleet.reports') }}">All Fleet Reports</a> @endif
                        @if ($canMenu('fleet_expense_report')) <a href="{{ route('fleet.reports.expenses') }}">Vehicle Expense Report</a> @endif
                        @if ($canMenu('fleet_maintenance_report')) <a href="{{ route('fleet.reports.maintenance') }}">Maintenance Report</a> @endif
                        @if ($canMenu('fleet_due_report')) <a href="{{ route('fleet.reports.maintenance-due') }}">Due & Overdue Report</a> @endif
                        @if ($canMenu('fleet_duty_history')) <a href="{{ route('fleet.reports.duty-history') }}">Staff Duty History</a> @endif
                    </div>
                </details>
            @endif

            @if ($canManageAdmin)
                <details class="nav-group">
                    <summary>Admin</summary>
                    <div class="nav-menu">
                        @if ($canMenu('users'))
                            <a href="{{ route('users.index') }}">Users</a>
                        @endif
                        @if ($canMenu('roles'))
                            <a href="{{ route('roles.index') }}">Roles</a>
                        @endif
                        @if ($currentUser?->isSuperAdmin())
                            <a href="{{ route('payment-account-access.index') }}">Payment Account Access</a>
                        @endif
                        @if ($canMenu('database_backup'))
                            <a href="{{ route('backup.database') }}">Download Backup</a>
                        @endif
                    </div>
                </details>
            @endif
        </nav>
        <div class="user-session">
            <div class="current-user" title="Logged in as {{ auth()->user()?->name }}">
                <span class="current-user-label">Logged in user</span>
                <strong class="current-user-name">{{ auth()->user()?->name }}</strong>
            </div>
            <form class="logout-form" method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn light" type="submit">Logout</button>
            </form>
        </div>
    </div>
    </header>
    <main class="main @yield('main_class')">
        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if (session('warning'))
            <div class="alert warning">{{ session('warning') }}</div>
        @endif

        @if (session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @include('partials.page_help')

        @yield('content')
    </main>
    <footer class="app-footer">Powered by Ultimate Solution</footer>
</div>
@php
    $serverRenderMs = defined('LARAVEL_START') ? round((microtime(true) - LARAVEL_START) * 1000) : null;
@endphp
@if ($serverRenderMs !== null)
    <div class="page-timing">Server: {{ number_format($serverRenderMs) }} ms</div>
@endif
<script>
window.addEventListener('load', function () {
    const timing = document.querySelector('.page-timing');
    if (! timing || ! performance?.timing) {
        return;
    }

    const browserMs = performance.timing.loadEventEnd - performance.timing.navigationStart;
    if (browserMs > 0) {
        timing.textContent += ' | Browser: ' + browserMs.toLocaleString() + ' ms';
    }
});

const navToggle = document.querySelector('.nav-toggle');
const appNav = document.querySelector('#app-nav');
if (appNav) {
    const currentPath = window.location.pathname.replace(/\/$/, '');
    const candidates = Array.from(appNav.querySelectorAll('a[href]'))
        .map(link => ({ link, path: new URL(link.href, window.location.origin).pathname.replace(/\/$/, '') }))
        .filter(item => item.path && (currentPath === item.path || currentPath.startsWith(item.path + '/')))
        .sort((a, b) => b.path.length - a.path.length);
    if (candidates[0]) {
        candidates[0].link.classList.add('is-active');
        candidates[0].link.closest('.nav-group')?.classList.add('has-active');
    }
}
if (navToggle && appNav) {
    navToggle.addEventListener('click', function () {
        const isOpen = appNav.classList.toggle('is-open');
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        navToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    });

    appNav.addEventListener('click', function (event) {
        if (event.target.closest('a') && window.matchMedia('(max-width: 980px)').matches) {
            appNav.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.setAttribute('aria-label', 'Open menu');
        }
    });
}

document.addEventListener('click', function (event) {
    if (event.target.closest('a, button, input, select, textarea, label, form, details, summary, .actions, .action-menu, [data-inline-field]')) {
        return;
    }

    const row = event.target.closest('tr[data-href]');
    if (row?.dataset.href) {
        window.location.href = row.dataset.href;
    }
});

// Keep the result-count text visible before and after every paginated table.
// The bottom copy is server-rendered; this adds the matching top copy beside
// the result table without changing each individual index page.
document.querySelectorAll('[data-pagination-summary]').forEach(function (wrap) {
    const summary = wrap.querySelector('.pagination-summary');
    if (! summary) return;

    const tablesBeforePagination = Array.from(document.querySelectorAll('table')).filter(function (table) {
        return Boolean(table.compareDocumentPosition(wrap) & Node.DOCUMENT_POSITION_FOLLOWING);
    });
    const resultTable = tablesBeforePagination.at(-1);
    if (! resultTable || resultTable.previousElementSibling?.classList.contains('pagination-summary-top')) return;

    const topSummary = document.createElement('div');
    topSummary.className = 'pagination-summary pagination-summary-top';
    topSummary.textContent = summary.textContent.trim();
    resultTable.insertAdjacentElement('beforebegin', topSummary);
});
</script>
</body>
</html>
