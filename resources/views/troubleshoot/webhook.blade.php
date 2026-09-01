@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; Webhook Settings</h1>
        <div class="muted">One shared <code>on-down</code> script on every PPP profile of every managed router. RouterOS calls it when a PPP session drops and posts the session's usage back to this app.</div>
    </div>
</div>

@error('url')<div class="alert error">{{ $message }}</div>@enderror

<form method="post" action="{{ route('troubleshoot.webhook.update') }}" class="card form-grid">
    @csrf
    @method('patch')

    <div class="full">
        <label style="display:flex;gap:8px;align-items:center">
            <input type="checkbox" name="enabled" value="1" style="width:auto" @checked(old('enabled', $enabled)) onchange="document.getElementById('webhook-url').required = this.checked">
            <strong>Enable webhook tracking</strong>
        </label>
        <span class="muted">On save: enabled &rarr; the <code>on-down</code> script is written to every PPP profile. Disabled &rarr; <code>on-down</code> is set back to empty on every PPP profile.</span>
    </div>

    <div class="full">
        <label>Webhook URL</label>
        <input id="webhook-url" name="url" type="url" value="{{ old('url', $url) }}" placeholder="https://your-app.example.com/api/ppp/usage" @required($enabled)>
        <span class="muted">Must be reachable from the routers themselves. Point it at this app's endpoint below (or a proxy in front of it).</span>
    </div>

    <div class="full">
        <label>Disconnect-log retention (days)</label>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input type="number" name="retention_days" min="0" max="3650" value="{{ old('retention_days', $retentionDays) }}" style="width:120px">
            <button class="btn light" type="submit" name="action" value="prune">Delete old rows now</button>
        </div>
        <span class="muted">Rows in <code>ppp_usage_logs</code> older than this are deleted automatically every night (and feed the Frequent Disconnects / MAC Changes / Connection Analytics pages). <strong>0 = keep forever.</strong></span>
    </div>

    <div class="full">
        <button class="btn" type="submit">Save &amp; push to all routers</button>
    </div>
</form>

<div class="card">
    <h2 style="margin-top:0">Receiver</h2>
    <table>
        <tbody>
            <tr><th style="text-align:left;width:180px">App endpoint</th><td><code>POST {{ $endpoint }}</code></td></tr>
            <tr><th style="text-align:left">Auth header</th><td><code>{{ $header }}: {{ $secret }}</code><div class="muted">RouterOS sends this automatically; the endpoint rejects requests without it.</div></td></tr>
            <tr><th style="text-align:left">Body</th><td><code>{"user","uptime","download","upload","caller_id","router_id"}</code> as JSON</td></tr>
        </tbody>
    </table>
    <p class="muted" style="margin:10px 0 0"><code>caller_id</code> is the PPPoE client MAC. On receipt the app matches it to an OLT ONU (by serial or a learned MAC) and records that ONU's last receiving optical power with the disconnect, shown on the Frequent Disconnects and Connection Analytics pages.</p>
    <details style="margin-top:10px">
        <summary>Example <code>on-down</code> script (router id 1)</summary>
        <pre style="white-space:pre-wrap;word-break:break-all;background:#f6f8fb;padding:10px;border-radius:6px">/tool fetch url="{{ $url ?: 'https://your-app.example.com/api/ppp/usage' }}" http-method=post http-header-field="Content-Type: application/json,{{ $header }}: {{ $secret }}" http-data="{\"user\":\"$user\",\"uptime\":\"$uptime\",\"download\":\"$\"bytes-in\"\",\"upload\":\"$\"bytes-out\"\",\"caller_id\":\"$\"caller-id\"\",\"router_id\":\"1\"}" output=none;</pre>
        <div class="muted">Uses RouterOS v7 <code>/tool fetch</code> with <code>http-data</code>. The script is identical on every profile — session variables carry the per-user values. Re-save this page to push the updated script to every router.</div>
    </details>
</div>
@endsection
