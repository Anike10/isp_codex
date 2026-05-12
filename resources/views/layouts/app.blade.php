<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ $title ?? 'Ultimate Solution' }}</title>
    <style>
        :root { color-scheme: light; --ink:#172033; --muted:#667085; --line:#d8dee9; --bg:#f4f7fb; --panel:#fff; --brand:#116149; --accent:#1d76c9; --warn:#b45309; --danger:#b42318; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, sans-serif; color:var(--ink); background:var(--bg); }
        a { color:inherit; text-decoration:none; }
        .shell { min-height:100vh; }
        .app-header { position:sticky; top:0; z-index:50; background:#14213d; color:white; border-bottom:1px solid rgba(255,255,255,.12); box-shadow:0 8px 24px rgba(15, 23, 42, .16); }
        .header-inner { max-width:1440px; margin:0 auto; padding:12px 20px; display:grid; grid-template-columns:auto minmax(0, 1fr) auto; align-items:center; gap:14px; }
        .brand { font-size:20px; font-weight:700; white-space:nowrap; }
        .nav { display:flex; gap:6px; align-items:center; flex-wrap:wrap; flex:1; padding:2px 0; }
        .nav a, .nav summary { color:#dbe7ff; padding:9px 11px; border-radius:6px; white-space:nowrap; font-size:14px; cursor:pointer; }
        .nav a:hover, .nav summary:hover, .nav details[open] summary { background:rgba(255,255,255,.1); color:white; }
        .nav summary { list-style:none; user-select:none; }
        .nav summary::-webkit-details-marker { display:none; }
        .nav summary::after { content:"▾"; margin-left:6px; font-size:11px; }
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
        .btn { border:0; border-radius:6px; background:var(--brand); color:white; padding:10px 14px; cursor:pointer; font-weight:700; display:inline-flex; align-items:center; min-height:38px; }
        .btn.secondary { background:var(--accent); }
        .btn.light { background:#e8eef7; color:var(--ink); }
        table { width:100%; border-collapse:collapse; background:white; border:1px solid var(--line); border-radius:8px; overflow:hidden; }
        tr[data-href] { cursor:pointer; }
        tbody tr:nth-child(even) td { background:#edf4f8; }
        tr[data-href]:hover td { background:#f6faf8; }
        th, td { padding:12px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top; }
        th { background:#edf2f7; font-size:13px; text-transform:uppercase; color:#475467; }
        tr:last-child td { border-bottom:0; }
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
        @media (max-width: 980px) {
            .stats, .two, .form-grid { grid-template-columns:1fr; }
            .header-inner { grid-template-columns:1fr auto; gap:8px 10px; padding:10px 12px 8px; }
            .brand { font-size:18px; min-width:0; overflow:hidden; text-overflow:ellipsis; }
            .nav { grid-column:1 / -1; width:100%; padding:2px 0 4px; gap:7px; }
            .nav a, .nav summary { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.08); }
            .nav-menu { position:static; min-width:100%; margin-top:6px; background:#fff; }
            .main { padding:16px 12px 28px; }
            .topbar { align-items:flex-start; flex-direction:column; }
            .actions { width:100%; }
            .actions .btn, .actions form, .actions input, .actions select { max-width:100%; }
            table { display:block; overflow-x:auto; }
            th, td { padding:10px; }
            h1 { font-size:24px; }
            .stat strong { font-size:22px; }
            .card { padding:14px; }
        }
        @media (max-width: 560px) {
            .app-header { position:static; }
            .header-inner { padding:9px 10px 7px; }
            .brand { font-size:17px; }
            .nav { margin:0 -10px; padding:4px 10px 6px; }
            .nav a, .nav summary { font-size:12px; padding:7px 9px; border-radius:999px; }
            .nav-menu a { border-radius:6px; font-size:12px; }
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
            .btn { min-height:36px; padding:9px 11px; font-size:13px; }
            th, td { padding:9px 8px; font-size:13px; }
            th { font-size:11px; }
            label { font-size:13px; }
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
        <div class="brand">Ultimate Solution</div>
        <nav class="nav">
            @php
                $canManageNetwork = auth()->user()?->hasPermission('manage_customers')
                    || auth()->user()?->hasPermission('manage_packages')
                    || auth()->user()?->hasPermission('manage_mikrotik_routers');
                $canManageBilling = auth()->user()?->hasPermission('manage_invoices')
                    || auth()->user()?->hasPermission('manage_payments')
                    || auth()->user()?->hasPermission('manage_payment_accounts')
                    || auth()->user()?->hasPermission('manage_expenses');
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
                        @if (auth()->user()?->hasPermission('manage_customers'))
                            <a href="{{ route('customers.index') }}">Customers</a>
                        @endif
                        @if (auth()->user()?->hasPermission('manage_packages'))
                            <a href="{{ route('packages.index') }}">Packages</a>
                        @endif
                        @if (auth()->user()?->hasPermission('manage_mikrotik_routers'))
                            <a href="{{ route('mikrotik-routers.index') }}">MikroTik Routers</a>
                        @endif
                    </div>
                </details>
            @endif

            @if ($canManageBilling)
                <details class="nav-group">
                    <summary>Billing</summary>
                    <div class="nav-menu">
                        @if (auth()->user()?->hasPermission('manage_invoices'))
                            <a href="{{ route('invoices.index') }}">Invoices</a>
                            <a href="{{ route('invoices.create') }}">Create Invoice</a>
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
                <a href="{{ route('products.index') }}">Inventory</a>
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

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</div>
<script>
document.addEventListener('click', function (event) {
    if (event.target.closest('a, button, input, select, textarea, label, form')) {
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
