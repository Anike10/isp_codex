<div class="actions" style="margin-bottom:16px">
    <a class="btn {{ request()->routeIs('fleet.reports') ? '' : 'light' }}" href="{{ route('fleet.reports') }}">All Fleet Reports</a>
    <a class="btn {{ request()->routeIs('fleet.reports.expenses') ? '' : 'light' }}" href="{{ route('fleet.reports.expenses') }}">Vehicle Expenses</a>
    <a class="btn {{ request()->routeIs('fleet.reports.maintenance') ? '' : 'light' }}" href="{{ route('fleet.reports.maintenance') }}">Maintenance</a>
    <a class="btn {{ request()->routeIs('fleet.reports.maintenance-due') ? '' : 'light' }}" href="{{ route('fleet.reports.maintenance-due') }}">Due & Overdue</a>
    <a class="btn {{ request()->routeIs('fleet.reports.duty-history') ? '' : 'light' }}" href="{{ route('fleet.reports.duty-history') }}">Staff Duty History</a>
</div>
