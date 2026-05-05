<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Computer & ISP Manager' }}</title>
    <style>
        :root { color-scheme: light; --ink:#172033; --muted:#667085; --line:#d8dee9; --bg:#f4f7fb; --panel:#fff; --brand:#116149; --accent:#1d76c9; --warn:#b45309; --danger:#b42318; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, sans-serif; color:var(--ink); background:var(--bg); }
        a { color:inherit; text-decoration:none; }
        .shell { display:grid; grid-template-columns:240px 1fr; min-height:100vh; }
        .sidebar { background:#14213d; color:white; padding:24px 18px; }
        .brand { font-size:20px; font-weight:700; margin-bottom:24px; }
        .nav { display:grid; gap:8px; }
        .nav a { color:#dbe7ff; padding:10px 12px; border-radius:6px; }
        .nav a:hover { background:rgba(255,255,255,.1); color:white; }
        .main { padding:28px; }
        .topbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:20px; }
        h1 { margin:0; font-size:28px; }
        h2 { margin:0 0 14px; font-size:20px; }
        .grid { display:grid; gap:16px; }
        .stats { grid-template-columns:repeat(6, minmax(0, 1fr)); }
        .two { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:8px; padding:18px; }
        .stat strong { display:block; font-size:26px; margin-top:8px; }
        .muted { color:var(--muted); }
        .actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .btn { border:0; border-radius:6px; background:var(--brand); color:white; padding:10px 14px; cursor:pointer; font-weight:700; display:inline-flex; align-items:center; min-height:38px; }
        .btn.secondary { background:var(--accent); }
        .btn.light { background:#e8eef7; color:var(--ink); }
        table { width:100%; border-collapse:collapse; background:white; border:1px solid var(--line); border-radius:8px; overflow:hidden; }
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
        .alert.error { background:#fff0f0; color:var(--danger); }
        .badge { display:inline-block; padding:4px 8px; border-radius:999px; background:#eef2ff; font-size:12px; font-weight:700; }
        .badge.due { background:#fff7ed; color:var(--warn); }
        .badge.paid, .badge.active { background:#ecfdf3; color:#027a48; }
        .badge.open, .badge.processing { background:#eff6ff; color:#175cd3; }
        .badge.low { background:#fff1f3; color:#c01048; }
        @media (max-width: 980px) {
            .shell { grid-template-columns:1fr; }
            .sidebar { position:static; }
            .stats, .two, .form-grid { grid-template-columns:1fr; }
            .main { padding:18px; }
            table { display:block; overflow-x:auto; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">KPS ISP Manager</div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('customers.index') }}">Customers</a>
            <a href="{{ route('packages.index') }}">Packages</a>
            <a href="{{ route('invoices.index') }}">Invoices</a>
            <a href="{{ route('payments.index') }}">Payments</a>
            <a href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
            <a href="{{ route('tickets.index') }}">Tickets</a>
            <a href="{{ route('products.index') }}">Inventory</a>
        </nav>
    </aside>
    <main class="main">
        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
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
</body>
</html>
