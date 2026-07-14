<div class="actions" style="margin-bottom:16px">
    <a class="btn {{ request()->routeIs('in-house-use.index') ? '' : 'light' }}" href="{{ route('in-house-use.index') }}">Create Issue</a>
    <a class="btn {{ request()->routeIs('in-house-use.report.employees') ? '' : 'light' }}" href="{{ route('in-house-use.report.employees') }}">Employee Assets</a>
    <a class="btn {{ request()->routeIs('in-house-use.report.used-stock') ? '' : 'light' }}" href="{{ route('in-house-use.report.used-stock') }}">Returned Used Stock</a>
    <a class="btn {{ request()->routeIs('in-house-use.report.history') ? '' : 'light' }}" href="{{ route('in-house-use.report.history') }}">Issue / Return History</a>
</div>
