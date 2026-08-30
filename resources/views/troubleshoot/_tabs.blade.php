@php($active = $active ?? '')
<div class="actions" style="gap:8px;margin:0 0 16px;flex-wrap:wrap">
    <a class="btn {{ $active === 'webhook' ? '' : 'light' }}" href="{{ route('troubleshoot.webhook.edit') }}">Webhook Settings</a>
    <a class="btn {{ $active === 'frequent' ? '' : 'light' }}" href="{{ route('troubleshoot.frequent-disconnects') }}">Frequent Disconnects</a>
    <a class="btn {{ $active === 'mac' ? '' : 'light' }}" href="{{ route('troubleshoot.mac-changes') }}">Frequent MAC Changes</a>
    <a class="btn {{ $active === 'analytics' ? '' : 'light' }}" href="{{ route('troubleshoot.analytics') }}">Connection Analytics</a>
    <a class="btn {{ $active === 'router-data' ? '' : 'light' }}" href="{{ route('troubleshoot.router-data') }}">Router Live Data</a>
</div>
