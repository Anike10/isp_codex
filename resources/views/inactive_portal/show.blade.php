<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    @php
        $pageBrandName = $organization?->please_call_brand_name ?: $organization?->name;
        $pageTitle = $organization?->please_call_title ?: 'Please Call';
        $pageMessage = $organization?->please_call_message ?: 'আপনার ইন্টারনেট সংযোগটি বর্তমানে বন্ধ আছে। সংযোগ চালু করতে নিচের যেকোনো নম্বরে কল করুন।';
        $pageFooter = $organization?->please_call_footer ?: 'বিল পরিশোধ বা সংযোগ-সংক্রান্ত সহায়তার জন্য কল করুন';
    @endphp
    <title>{{ $pageTitle }}{{ $pageBrandName ? ' - '.$pageBrandName : '' }}</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; color:#172033; background:radial-gradient(circle at top, #eef6ff 0, #f7f9fc 48%, #edf1f7 100%); }
        .card { width:min(100%, 520px); padding:38px 30px 32px; text-align:center; background:#fff; border:1px solid #dfe7f1; border-radius:24px; box-shadow:0 24px 65px rgba(31, 52, 79, .14); }
        .mark { width:72px; height:72px; margin:0 auto 18px; display:grid; place-items:center; border-radius:22px; background:#e9f3ff; color:#1468b3; }
        .mark svg { width:38px; height:38px; }
        .organization { margin:0 0 7px; color:#536277; font-size:15px; font-weight:700; letter-spacing:.02em; }
        h1 { margin:0; font-size:clamp(36px, 10vw, 56px); line-height:1; letter-spacing:-.04em; color:#102a43; }
        .message { margin:20px auto 24px; max-width:420px; color:#526277; font-size:17px; line-height:1.65; }
        .numbers { display:grid; gap:12px; }
        .number { display:flex; align-items:center; justify-content:center; gap:10px; min-height:58px; padding:12px 18px; border-radius:15px; color:#fff; background:#1473c9; text-decoration:none; font-size:clamp(20px, 6vw, 28px); font-weight:800; letter-spacing:.02em; box-shadow:0 8px 20px rgba(20, 115, 201, .22); }
        .number:hover, .number:focus-visible { background:#0e5fa9; transform:translateY(-1px); }
        .number svg { width:23px; height:23px; flex:0 0 auto; }
        .empty { padding:16px; border-radius:14px; color:#8a4b08; background:#fff7e6; border:1px solid #ffe1ad; line-height:1.55; }
        .hint { margin:22px 0 0; color:#7a8798; font-size:13px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"/></svg>
        </div>

        @if ($pageBrandName)
            <p class="organization">{{ $pageBrandName }}</p>
        @endif
        <h1>{{ $pageTitle }}</h1>
        <p class="message">{{ $pageMessage }}</p>

        @if ($numbers !== [])
            <div class="numbers">
                @foreach ($numbers as $number)
                    <a class="number" href="tel:{{ preg_replace('/[^0-9+]/', '', $number) }}">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"/></svg>
                        <span>{{ $number }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="empty">যোগাযোগের নম্বর এখনো সেট করা হয়নি। অনুগ্রহ করে আপনার ইন্টারনেট সেবাদাতার সঙ্গে যোগাযোগ করুন।</div>
        @endif

        <p class="hint">{{ $pageFooter }}</p>
    </main>
</body>
</html>
