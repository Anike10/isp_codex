<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=us-20260718">
    <title>Login - {{ $appOrganization?->name ?? config('app.name') }}</title>
    <style>
        :root { --ink:#172033; --muted:#667085; --line:#d8dee9; --bg:#f4f7fb; --brand:#116149; --danger:#b42318; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:var(--bg); color:var(--ink); font-family:Arial, sans-serif; }
        .login { width:min(420px, calc(100vw - 32px)); background:#fff; border:1px solid var(--line); border-radius:8px; padding:26px; }
        h1 { margin:0; font-size:28px; color:#0b3f31; }
        .muted { color:var(--muted); margin:6px 0 22px; }
        label { display:block; font-weight:700; margin-bottom:6px; }
        input { width:100%; border:1px solid var(--line); border-radius:6px; padding:11px; font:inherit; margin-bottom:14px; }
        .check { display:flex; align-items:center; gap:8px; margin-bottom:16px; }
        .check input { width:auto; margin:0; }
        .btn { width:100%; border:0; border-radius:6px; background:var(--brand); color:white; padding:11px 14px; font-weight:700; cursor:pointer; }
        .error { background:#fff0f0; color:var(--danger); border-radius:6px; padding:10px 12px; margin-bottom:14px; }
        .powered-by { margin-top:18px; color:var(--muted); font-size:13px; text-align:center; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/gorgeous-theme.css') }}?v=20260719-17">
</head>
<body class="login-theme">
    <form method="post" action="{{ route('login.store') }}" class="login">
        @csrf
        <h1>{{ $appOrganization?->name ?? config('app.name') }}</h1>
        <div class="muted">Sign in to manage your ISP system</div>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>

        <label>Password</label>
        <input type="password" name="password" required>

        <label class="check">
            <input type="checkbox" name="remember" value="1">
            Remember me
        </label>

        <button class="btn" type="submit">Login</button>
        <div class="powered-by">Powered by Ultimate Solution</div>
    </form>
</body>
</html>
