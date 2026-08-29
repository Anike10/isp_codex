@extends('layouts.app')

@section('content')
@include('mikrotik_routers._form_styles')

<div class="topbar">
    <div>
        <h1>Add MikroTik Router</h1>
        <div class="muted">Save the RouterOS login so the app can import from and sync this router.</div>
    </div>
    <a class="btn light" href="{{ route('mikrotik-routers.index') }}">Back to Routers</a>
</div>

@if ($errors->any())
    <div class="alert error">Please fix the highlighted fields and save again.</div>
@endif

<form method="post" action="{{ route('mikrotik-routers.store') }}" class="router-form" autocomplete="off">
    @csrf
    <div aria-hidden="true" style="position:fixed;left:-10000px;top:-10000px;width:1px;height:1px;overflow:hidden">
        <input name="browser_login_hint" autocomplete="username" tabindex="-1">
        <input type="password" name="browser_password_hint" autocomplete="current-password" tabindex="-1">
    </div>

    <section class="rf-card">
        <div class="rf-card__head">
            <h2>Identity &amp; reachability</h2>
            <p class="rf-card__sub">How the app finds and talks to this RouterOS device.</p>
        </div>
        <div class="rf-grid">
            <div class="rf-field">
                <label for="rf-name">Router name</label>
                <input id="rf-name" name="name" value="{{ old('name', 'Main MikroTik') }}" required>
            </div>
            <div class="rf-field">
                <label for="rf-ip">IP address</label>
                <input id="rf-ip" name="ip_address" value="{{ old('ip_address', '192.168.6.1') }}" required>
            </div>
            <div class="rf-field">
                <label for="rf-port">Port</label>
                <input id="rf-port" type="number" min="1" max="65535" name="api_port" value="{{ old('api_port', 8728) }}" required>
                <small class="rf-hint">Binary API is usually <code>8728</code>. REST uses your custom www / www-ssl port.</small>
            </div>
            <div class="rf-field">
                <label for="router-transport">Connection type</label>
                <select id="router-transport" name="transport" required>
                    <option value="api" @selected(old('transport', 'api') === 'api')>Binary RouterOS API</option>
                    <option value="rest" @selected(old('transport') === 'rest')>REST API (www service)</option>
                </select>
                <small class="rf-hint">REST needs RouterOS v7 with the <code>www</code> service enabled.</small>
            </div>
            <div class="rf-check" id="router-rest-secure-row" style="{{ old('transport') === 'rest' ? '' : 'display:none' }}">
                <input type="checkbox" id="rf-rest-secure" name="rest_secure" value="1" @checked(old('rest_secure'))>
                <div>
                    <strong><label for="rf-rest-secure" style="font-weight:700">REST uses HTTPS (www-ssl / self-signed)</label></strong>
                    <small class="rf-hint">Tick when the router serves the REST API over TLS.</small>
                </div>
            </div>
        </div>
    </section>

    <section class="rf-card">
        <div class="rf-card__head">
            <h2>API credentials &amp; state</h2>
            <p class="rf-card__sub">The RouterOS login the app authenticates with, and whether it is used.</p>
        </div>
        <div class="rf-grid">
            <div class="rf-field">
                <label for="router-username">API username</label>
                <input id="router-username" name="router_api_username" value="{{ old('router_api_username', old('username', 'admin')) }}" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" required>
            </div>
            <div class="rf-field">
                <label for="router-password">API password</label>
                <input id="router-password" type="password" name="router_api_password" autocomplete="new-password" required>
            </div>
            <div class="rf-field">
                <label for="rf-status">Status</label>
                <select id="rf-status" name="status" required>
                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                </select>
                <small class="rf-hint">Inactive routers are skipped by every scheduled sync.</small>
            </div>
            <div class="rf-check">
                <input type="checkbox" id="rf-read-only" name="read_only" value="1" @checked(old('read_only'))>
                <div>
                    <strong><label for="rf-read-only" style="font-weight:700">API user is read-only (import only)</label></strong>
                    <small class="rf-hint">Importing secrets, profiles and pools still works; the app never pushes changes, and this router's users/profiles show as &ldquo;Read-only&rdquo;.</small>
                </div>
            </div>
        </div>
    </section>

    <section class="rf-card">
        <div class="rf-card__head">
            <h2>Sync schedule</h2>
            <p class="rf-card__sub">How often the background jobs reconcile this router.</p>
        </div>
        <div class="rf-grid">
            <div class="rf-field">
                <label for="rf-pppoe-interval">PPPoE sync interval</label>
                <div class="rf-input-suffix">
                    <input id="rf-pppoe-interval" type="number" min="1" max="365" name="pppoe_sync_interval_days" value="{{ old('pppoe_sync_interval_days', 10) }}" required>
                    <span>days</span>
                </div>
                <small class="rf-hint">Full reconcile of every party's PPPoE secret &amp; profile. Default 10 days; the scheduler checks hourly whether this router is due.</small>
            </div>
            <div class="rf-field">
                <label for="rf-mac-interval">Active connection MAC sync interval</label>
                <div class="rf-input-suffix">
                    <input id="rf-mac-interval" type="number" min="1" max="365" name="active_mac_sync_interval_days" value="{{ old('active_mac_sync_interval_days', 1) }}" required>
                    <span>days</span>
                </div>
                <small class="rf-hint">Polls <code>/ppp/active</code> and copies each session's device MAC onto the matching party. Default 1 day.</small>
            </div>
            <div class="rf-field">
                <label for="rf-inactive-profile">Inactive PPPoE profile</label>
                <input id="rf-inactive-profile" name="inactive_pppoe_profile" value="{{ old('inactive_pppoe_profile', 'inactive') }}" required>
                <small class="rf-hint">Suspended users are moved to this profile, not disabled.</small>
            </div>
        </div>
    </section>

    <section class="rf-card">
        <div class="rf-card__head">
            <h2>Notes</h2>
        </div>
        <div class="rf-grid">
            <div class="rf-field rf-field--full">
                <label for="rf-notes">Internal notes</label>
                <textarea id="rf-notes" name="notes">{{ old('notes') }}</textarea>
            </div>
        </div>
    </section>

    <div class="rf-actions">
        <a class="btn light" href="{{ route('mikrotik-routers.index') }}">Cancel</a>
        <button class="btn" type="submit">Save router</button>
    </div>
</form>

<script>
    (function () {
        const transport = document.getElementById('router-transport');
        const secureRow = document.getElementById('router-rest-secure-row');
        if (!transport || !secureRow) return;
        transport.addEventListener('change', function () {
            secureRow.style.display = transport.value === 'rest' ? '' : 'none';
        });
    })();
</script>
@endsection
