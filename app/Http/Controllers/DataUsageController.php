<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PppUsageLog;
use App\Services\PppWebhookService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only reporting over {@see PppUsageLog} rows: how much each party has
 * downloaded/uploaded (bytes are per-session, logged when a PPP session
 * disconnects). Two views: every party's total for a window, and one
 * party's usage broken down by day or by individual session.
 *
 * The time window is either a rolling "last N days" preset or an explicit
 * from/to date range (the range wins when supplied).
 *
 * Shares its retention window with the other Troubleshoot reports — see
 * {@see PppWebhookService::retentionDays()} and troubleshoot._retention.
 */
class DataUsageController extends Controller
{
    public function __construct(private readonly PppWebhookService $webhook) {}

    /** One row per user with total download/upload/sessions for the selected window. */
    public function index(Request $request)
    {
        $saved = $request->session()->get('troubleshoot.data_usage_defaults', []);
        $sortable = ['username', 'sessions', 'download_bytes', 'upload_bytes', 'total_bytes', 'last_at'];

        $requestedSort = $request->query('sort', $saved['sort'] ?? null);
        $sort = in_array($requestedSort, $sortable, true) ? $requestedSort : 'total_bytes';
        $dir = ($request->query('dir', $saved['dir'] ?? null) === 'asc') ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', $request->has('search') ? '' : ($saved['search'] ?? '')));
        $perPage = max(10, min(200, (int) $request->query('per_page', 50)));

        $window = $this->resolveWindow($request, $saved);

        $this->rememberDefaults($request, 'troubleshoot.data_usage_defaults', [
            'sort' => $sort,
            'dir' => $dir,
            'search' => $search,
            'days' => $window['days'],
            'from' => $window['from']?->toDateString(),
            'to' => $window['to']?->toDateString(),
        ]);

        $rows = PppUsageLog::query()
            ->where('disconnected_at', '>=', $window['since'])
            ->when($window['until'], fn ($query) => $query->where('disconnected_at', '<=', $window['until']))
            ->when($search !== '', fn ($query) => $query->where('username', 'like', "%{$search}%"))
            ->groupBy('username')
            ->select('username')
            ->selectRaw('count(*) as sessions')
            ->selectRaw('sum(download_bytes) as download_bytes')
            ->selectRaw('sum(upload_bytes) as upload_bytes')
            ->selectRaw('sum(download_bytes + upload_bytes) as total_bytes')
            ->selectRaw('max(disconnected_at) as last_at')
            ->orderBy($sort, $dir)
            ->orderBy('username')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachCustomers($rows->getCollection());

        return view('troubleshoot.data_usage', [
            'rows' => $rows,
            'sort' => $sort,
            'dir' => $dir,
            'search' => $search,
            'days' => $window['days'],
            'from' => $window['from'],
            'to' => $window['to'],
            'rangeActive' => $window['rangeActive'],
            'since' => $window['since'],
            'until' => $window['until'],
            'retentionDays' => $this->webhook->retentionDays(),
        ]);
    }

    /** One party's usage, grouped by day or listed one row per session. */
    public function show(Request $request, Customer $customer)
    {
        // Fall back to whatever the party picker last remembered, so a bare link
        // from the summary table opens with the admin's preferred view/window.
        $saved = $request->session()->get('troubleshoot.data_usage_party_defaults', []);

        $requestedView = $request->query('view', $saved['view'] ?? null);
        $view = $requestedView === 'session' ? 'session' : 'day';
        $perPage = max(10, min(200, (int) $request->query('per_page', 50)));

        $window = $this->resolveWindow($request, $saved);

        $usernames = collect([$customer->connection_id, $customer->mikrotik_username])
            ->filter()
            ->map(fn ($username) => mb_strtolower(trim((string) $username)))
            ->unique()
            ->values();

        $base = PppUsageLog::query()
            ->where('disconnected_at', '>=', $window['since'])
            ->when($window['until'], fn ($query) => $query->where('disconnected_at', '<=', $window['until']))
            ->when(
                $usernames->isNotEmpty(),
                fn ($query) => $query->whereIn(DB::raw('lower(username)'), $usernames),
                fn ($query) => $query->whereRaw('1 = 0')
            );

        $totals = (clone $base)
            ->selectRaw('count(*) as sessions')
            ->selectRaw('sum(download_bytes) as download_bytes')
            ->selectRaw('sum(upload_bytes) as upload_bytes')
            ->selectRaw('sum(download_bytes + upload_bytes) as total_bytes')
            ->first();

        if ($view === 'day') {
            $rows = (clone $base)
                ->selectRaw('DATE(disconnected_at) as day')
                ->selectRaw('count(*) as sessions')
                ->selectRaw('sum(download_bytes) as download_bytes')
                ->selectRaw('sum(upload_bytes) as upload_bytes')
                ->selectRaw('sum(download_bytes + upload_bytes) as total_bytes')
                ->groupBy('day')
                ->orderByDesc('day')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $rows = (clone $base)
                ->with('router')
                ->orderByDesc('disconnected_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('troubleshoot.data_usage_show', [
            'customer' => $customer,
            'rows' => $rows,
            'view' => $view,
            'days' => $window['days'],
            'from' => $window['from'],
            'to' => $window['to'],
            'rangeActive' => $window['rangeActive'],
            'since' => $window['since'],
            'until' => $window['until'],
            'totals' => $totals,
            'retentionDays' => $this->webhook->retentionDays(),
        ]);
    }

    /**
     * Party picker for the single-party detail page. Carries a full view/window
     * filter set, which it forwards to {@see show()} on submit (and can remember
     * as this page's default). It is the menu entry point — the detail page
     * itself needs a party in the URL.
     */
    public function select(Request $request)
    {
        $key = 'troubleshoot.data_usage_party_defaults';
        $saved = $request->session()->get($key, []);

        $requestedView = $request->query('view', $saved['view'] ?? null);
        $view = $requestedView === 'session' ? 'session' : 'day';
        $window = $this->resolveWindow($request, $saved);

        $this->rememberDefaults($request, $key, [
            'view' => $view,
            'days' => $window['days'],
            'from' => $window['from']?->toDateString(),
            'to' => $window['to']?->toDateString(),
        ]);

        if ($request->filled('customer') && ($customer = Customer::find($request->integer('customer')))) {
            $params = ['customer' => $customer, 'view' => $view];

            if ($window['rangeActive']) {
                $params['from'] = $window['from']?->toDateString();
                $params['to'] = $window['to']?->toDateString();
            } else {
                $params['days'] = $window['days'];
            }

            return redirect()->route('troubleshoot.data-usage.show', array_filter(
                $params,
                fn ($value) => $value !== null && $value !== ''
            ));
        }

        $parties = Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'connection_id', 'mikrotik_username'])
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'label' => trim($c->name . ' — ' . ($c->connection_id ?: $c->mikrotik_username ?: ('#' . $c->id))),
            ]);

        return view('troubleshoot.data_usage_select', [
            'parties' => $parties,
            'selected' => $request->integer('customer') ?: null,
            'view' => $view,
            'days' => $window['days'],
            'from' => $window['from'],
            'to' => $window['to'],
            'rangeActive' => $window['rangeActive'],
            'madeDefault' => $request->query('make_default') === '1',
        ]);
    }

    /**
     * Work out the time window from the request: an explicit from/to date range
     * takes precedence, otherwise a rolling "last N days" preset.
     *
     * @param  array<string, mixed>  $saved  Remembered defaults, if any.
     * @return array{since: Carbon, until: ?Carbon, days: int, from: ?Carbon, to: ?Carbon, rangeActive: bool}
     */
    private function resolveWindow(Request $request, array $saved = []): array
    {
        $days = max(1, min(3650, (int) $request->query('days', $saved['days'] ?? 30)));

        $from = $this->parseDate($request->query('from', $saved['from'] ?? null));
        $to = $this->parseDate($request->query('to', $saved['to'] ?? null));

        // Ignore a nonsensical inverted range rather than returning zero rows silently.
        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $rangeActive = $from !== null || $to !== null;

        if ($rangeActive) {
            $since = ($from ?? Carbon::createFromTimestamp(0))->copy()->startOfDay();
            $until = ($to ?? now())->copy()->endOfDay();

            return compact('since', 'until', 'days', 'from', 'to', 'rangeActive');
        }

        return [
            'since' => now()->subDays($days),
            'until' => null,
            'days' => $days,
            'from' => null,
            'to' => null,
            'rangeActive' => false,
        ];
    }

    /** Parse a `Y-m-d` query value into a Carbon date, or null when absent/invalid. */
    private function parseDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        // Reject rolled-over values like 2026-13-99 that createFromFormat accepts.
        if ($date === false || $date->toDateString() !== $value) {
            return null;
        }

        return $date->startOfDay();
    }

    /** Persist the current filter set as this page's default when `?make_default=1`. */
    private function rememberDefaults(Request $request, string $key, array $values): void
    {
        if ($request->query('make_default') === '1') {
            $request->session()->put($key, $values);
        }
    }

    /**
     * Resolve each aggregate row's `username` to a party (case-insensitive on
     * connection_id / mikrotik_username) so the summary table can link to it.
     *
     * @param  Collection<int, Model>  $rows
     */
    private function attachCustomers(Collection $rows): void
    {
        $names = $rows->pluck('username')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        $byName = [];
        Customer::query()
            ->select('id', 'name', 'connection_id', 'mikrotik_username', 'status')
            ->where(function ($query) use ($names): void {
                $query->whereIn(DB::raw('lower(connection_id)'), $names)
                    ->orWhereIn(DB::raw('lower(mikrotik_username)'), $names);
            })
            ->get()
            ->each(function (Customer $customer) use (&$byName): void {
                foreach ([$customer->connection_id, $customer->mikrotik_username] as $identifier) {
                    $key = mb_strtolower(trim((string) $identifier));
                    if ($key !== '' && ! isset($byName[$key])) {
                        $byName[$key] = $customer;
                    }
                }
            });

        $rows->each(function ($row) use ($byName): void {
            $row->matched_customer = $byName[mb_strtolower(trim((string) $row->username))] ?? null;
        });
    }
}
