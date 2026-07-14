<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ $title ?? 'Kushtia Municipality' }}</title>
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
        .nav { display:flex; gap:6px; align-items:center; flex-wrap:wrap; flex:1; padding:2px 0; }
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
        .logout-form { margin:0; }
        .logout-form .btn { min-height:34px; padding:8px 12px; white-space:nowrap; }
        .main { max-width:1440px; margin:0 auto; padding:24px 20px 34px; }
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
        .badge.processed, .badge.balance { background:#ecfdf3; color:#027a48; }
        .badge.pending { background:#fff7ed; color:var(--warn); }
        .badge.duplicate { background:#eef2ff; color:#175cd3; }
        .badge.failed { background:#fff0f0; color:var(--danger); }
        .badge.online { background:#ecfdf3; color:#027a48; }
        .badge.offline { background:#fff0f0; color:var(--danger); }
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
            .logout-form { grid-column:2; grid-row:1; }
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
            .logout-form { margin-left:auto; }
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
</head>
<body>
<div class="shell">
    <header class="app-header">
    <div class="header-inner">
        <div class="brand">Kushtia Municipality</div>
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-controls="app-nav" aria-expanded="false">
            <span class="nav-toggle-lines"></span>
        </button>
        <nav class="nav" id="app-nav">
            @php
                $canManageNetwork = auth()->user()?->hasPermission('manage_packages')
                    || auth()->user()?->hasPermission('manage_mikrotik_routers');
                $canManageBilling = auth()->user()?->hasPermission('manage_invoices')
                    || auth()->user()?->hasPermission('manage_payments')
                    || auth()->user()?->hasPermission('manage_payment_accounts')
                    || auth()->user()?->hasPermission('manage_customers')
                    || auth()->user()?->hasPermission('manage_expenses');
                $canManageWarranty = auth()->user()?->hasPermission('view_warranty_claims')
                    || auth()->user()?->hasPermission('manage_warranty_claims')
                    || auth()->user()?->hasPermission('manage_products');
                $canManageAdmin = auth()->user()?->hasPermission('manage_users')
                    || auth()->user()?->hasPermission('download_backup');
            @endphp
            @if (auth()->user()?->hasPermission('view_dashboard'))
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @endif

            @if ($canManageNetwork)
                <details class="nav-group">
                    <summary>Network</summary>
                    <div class="nav-menu">
                        @if (auth()->user()?->hasPermission('manage_packages'))
                            <a href="{{ route('packages.index') }}">Packages</a>
                        @endif
                        @if (auth()->user()?->hasPermission('manage_mikrotik_routers'))
                            <a href="{{ route('mikrotik-routers.index') }}">MikroTik Routers</a>
                            <a href="{{ route('network-map.index') }}">FTTX Network Map</a>
                            <a href="{{ route('olt-onus.index') }}">OLT ONUs</a>
                            <a href="{{ route('olt-onus.deny-list') }}">ONU Deny List</a>
                            <a href="{{ route('olt-onus.auto-discovery') }}">Auto Discovery List</a>
                            <a href="{{ route('olt-onus.protocol-profiles.index') }}">OLT Protocol/Profile</a>
                        @endif
                    </div>
                </details>
            @endif

            @if ($canManageBilling)
                <details class="nav-group">
                    <summary>Billing</summary>
                    <div class="nav-menu">
                        @if (auth()->user()?->hasPermission('manage_customers'))
                            <a href="{{ route('customers.index') }}">Parties</a>
                        @endif
                        @if (auth()->user()?->hasPermission('manage_invoices'))
                            <a href="{{ route('invoices.index') }}">Invoices</a>
                            <a href="{{ route('invoices.create') }}">Create Invoice</a>
                            <a href="{{ route('sale-returns.index') }}">Sale Returns</a>
                            <a href="{{ route('quotations.index') }}">Quotations</a>
                            <a href="{{ route('quotations.create') }}">Create Quotation</a>
                            <a href="{{ route('invoices.payment-note-default.edit') }}">Payment Note Default</a>
                        @endif
                        @if (auth()->user()?->hasPermission('manage_payments'))
                            <a href="{{ route('payments.index') }}">Payments</a>
                            <a href="{{ route('bkash-sms-payments.index') }}">bKash SMS</a>
                        @endif
                        @if (auth()->user()?->hasPermission('manage_payment_accounts'))
                            <a href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
                            <a href="{{ route('accounting.ledger') }}">Accounting Ledger</a>
                        @endif
                        @if (auth()->user()?->hasPermission('manage_expenses'))
                            <a href="{{ route('employees.index') }}">Employees</a>
                            <a href="{{ route('expenses.index') }}">Salary & Expenses</a>
                        @endif
                    </div>
                </details>
            @endif

            @if (auth()->user()?->hasPermission('manage_tickets'))
                <a href="{{ route('tickets.index') }}">Tickets</a>
            @endif
            @if (auth()->user()?->hasPermission('manage_products'))
                <details class="nav-group">
                    <summary>Inventory</summary>
                    <div class="nav-menu">
                        <a href="{{ route('products.index') }}">Products</a>
                        <a href="{{ route('warehouses.index') }}">Warehouses</a>
                        <a href="{{ route('warehouse-transfers.create') }}">Stock Transfer</a>
                        <a href="{{ route('warehouse-movements.index') }}">Stock History</a>
                        <a href="{{ route('product-categories.index') }}">Product Categories</a>
                        <a href="{{ route('purchase-bills.index') }}">Purchase Bills</a>
                    </div>
                </details>
            @endif

            @if ($canManageWarranty)
                <details class="nav-group">
                    <summary>Warranty</summary>
                    <div class="nav-menu">
                        @if (auth()->user()?->hasPermission('view_warranty_claims') || auth()->user()?->hasPermission('manage_products'))
                            <a href="{{ route('warranty-claims.index') }}">Warranty Claims</a>
                        @endif
                        @if (auth()->user()?->hasPermission('manage_warranty_claims') || auth()->user()?->hasPermission('manage_products'))
                            <a href="{{ route('warranty-claims.create') }}">New Claim</a>
                        @endif
                    </div>
                </details>
            @endif
            @if (auth()->user()?->hasPermission('manage_expenses'))
                <a href="{{ route('expenses.index') }}">Expenses</a>
            @endif

            @if ($canManageAdmin)
                <details class="nav-group">
                    <summary>Admin</summary>
                    <div class="nav-menu">
                        @if (auth()->user()?->hasPermission('manage_users'))
                            <a href="{{ route('users.index') }}">Users</a>
                            <a href="{{ route('roles.index') }}">Roles</a>
                        @endif
                        @if (auth()->user()?->hasPermission('download_backup'))
                            <a href="{{ route('backup.database') }}">Download Backup</a>
                        @endif
                    </div>
                </details>
            @endif
        </nav>
        <form class="logout-form" method="post" action="{{ route('logout') }}">
            @csrf
            <button class="btn light" type="submit">Logout</button>
        </form>
    </div>
    </header>
    <main class="main">
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
    if (event.target.closest('a, button, input, select, textarea, label, form, details, summary, .actions, .action-menu')) {
        return;
    }

    const row = event.target.closest('tr[data-href]');
    if (row?.dataset.href) {
        window.location.href = row.dataset.href;
    }
});
</script>
</body>
</html>
