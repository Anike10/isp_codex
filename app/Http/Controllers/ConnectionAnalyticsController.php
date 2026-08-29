<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\PppUsageLog;
use App\Services\PppWebhookService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only reporting over {@see PppUsageLog} rows (one per PPP disconnect
 * received from a router `on-down` webhook): the "Frequent Disconnects" filter
 * and the all-user "Connection Analytics" summary.
 */
class ConnectionAnalyticsController extends Controller
{
    public function __construct(private readonly PppWebhookService $webhook) {}

    /**
     * Set (and optionally apply now) the `ppp_usage_logs` retention window that
     * feeds all three Troubleshoot report pages. Shared by their inline control.
     */
    public function updateRetention(Request $request)
    {
        $days = (int) $request->validate([
            'retention_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ])['retention_days'];

        $this->webhook->setRetentionDays($days);

        if ($request->input('action') === 'prune') {
            $removed = $this->webhook->pruneUsageLogs();

            return back()->with('success', $removed > 0
                ? "Deleted {$removed} disconnect-log row(s) older than {$days} day(s)."
                : 'Retention saved. No rows are older than the window.');
        }

        return back()->with('success', $days > 0
            ? "Saved — disconnect-log rows older than {$days} day(s) are deleted every night."
            : 'Saved — disconnect logs are now kept forever.');
    }

    /** List users whose disconnect count crosses a threshold within a window. */
    public function frequentDisconnects(Request $request)
    {
        $saved = $request->session()->get('troubleshoot.frequent_disconnects_defaults', []);

        $hours = max(1, min(8760, (int) $request->query('hours', $saved['hours'] ?? 24)));
        $minCount = max(1, min(10000, (int) $request->query('min_count', $saved['min_count'] ?? 10)));
        $routerId = $this->resolvedRouterId($request, $saved);
        $perPage = max(10, min(200, (int) $request->query('per_page', 50)));

        $this->rememberDefaults($request, 'troubleshoot.frequent_disconnects_defaults', [
            'hours' => $hours,
            'min_count' => $minCount,
            'router' => $routerId,
        ]);

        $since = now()->subHours($hours);

        $rows = PppUsageLog::query()
            ->where('disconnected_at', '>=', $since)
            ->when($routerId, fn ($query) => $query->where('mikrotik_router_id', $routerId))
            ->groupBy('username')
            ->havingRaw('count(*) >= ?', [$minCount])
            ->select('username')
            ->selectRaw('count(*) as disconnects')
            ->selectRaw('count(distinct mikrotik_router_id) as routers')
            ->selectRaw('max(disconnected_at) as last_at')
            ->orderByDesc('disconnects')
            ->orderBy('username')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachCustomers($rows->getCollection());
        $this->attachOnuReadings($rows->getCollection());

        return view('troubleshoot.frequent_disconnects', [
            'rows' => $rows,
            'routers' => MikrotikRouter::orderBy('name')->get(['id', 'name']),
            'hours' => $hours,
            'minCount' => $minCount,
            'routerId' => $routerId,
            'since' => $since,
            'retentionDays' => $this->webhook->retentionDays(),
        ]);
    }

    /**
     * List users whose device MAC (webhook `caller_id`) changed often within a
     * window — i.e. connected from several different devices. A high count can
     * mean a swapped router, a shared line, or MAC spoofing.
     */
    public function macChanges(Request $request)
    {
        $saved = $request->session()->get('troubleshoot.mac_changes_defaults', []);

        $hours = max(1, min(8760, (int) $request->query('hours', $saved['hours'] ?? 24)));
        $minMacs = max(2, min(100, (int) $request->query('min_macs', $saved['min_macs'] ?? 3)));
        $routerId = $this->resolvedRouterId($request, $saved);
        $perPage = max(10, min(200, (int) $request->query('per_page', 50)));

        $this->rememberDefaults($request, 'troubleshoot.mac_changes_defaults', [
            'hours' => $hours,
            'min_macs' => $minMacs,
            'router' => $routerId,
        ]);

        $since = now()->subHours($hours);

        $rows = PppUsageLog::query()
            ->where('disconnected_at', '>=', $since)
            ->whereNotNull('caller_id')
            ->when($routerId, fn ($query) => $query->where('mikrotik_router_id', $routerId))
            ->groupBy('username')
            ->havingRaw('count(distinct caller_id) >= ?', [$minMacs])
            ->select('username')
            ->selectRaw('count(distinct caller_id) as mac_count')
            ->selectRaw('count(*) as events')
            ->selectRaw('max(disconnected_at) as last_at')
            ->orderByDesc('mac_count')
            ->orderBy('username')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachCustomers($rows->getCollection());
        $this->attachRecentMacs($rows->getCollection(), $since, $routerId);

        return view('troubleshoot.mac_changes', [
            'rows' => $rows,
            'routers' => MikrotikRouter::orderBy('name')->get(['id', 'name']),
            'hours' => $hours,
            'minMacs' => $minMacs,
            'routerId' => $routerId,
            'since' => $since,
            'retentionDays' => $this->webhook->retentionDays(),
        ]);
    }

    /** One row per user with disconnect counts across 24h / 7d / 30d / all time. */
    public function index(Request $request)
    {
        $saved = $request->session()->get('troubleshoot.analytics_defaults', []);
        $sortable = ['username', 'd24h', 'd7d', 'd30d', 'dall', 'last_at'];

        $requestedSort = $request->query('sort', $saved['sort'] ?? null);
        $sort = in_array($requestedSort, $sortable, true) ? $requestedSort : 'dall';
        $dir = ($request->query('dir', $saved['dir'] ?? null) === 'asc') ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', $request->has('search') ? '' : ($saved['search'] ?? '')));
        $routerId = $this->resolvedRouterId($request, $saved);
        $perPage = max(10, min(200, (int) $request->query('per_page', 50)));

        $this->rememberDefaults($request, 'troubleshoot.analytics_defaults', [
            'sort' => $sort,
            'dir' => $dir,
            'search' => $search,
            'router' => $routerId,
        ]);

        $d1 = now()->subDay();
        $d7 = now()->subDays(7);
        $d30 = now()->subDays(30);

        $rows = PppUsageLog::query()
            ->when($routerId, fn ($query) => $query->where('mikrotik_router_id', $routerId))
            ->when($search !== '', fn ($query) => $query->where('username', 'like', "%{$search}%"))
            ->groupBy('username')
            ->select('username')
            ->selectRaw('sum(case when disconnected_at >= ? then 1 else 0 end) as d24h', [$d1])
            ->selectRaw('sum(case when disconnected_at >= ? then 1 else 0 end) as d7d', [$d7])
            ->selectRaw('sum(case when disconnected_at >= ? then 1 else 0 end) as d30d', [$d30])
            ->selectRaw('count(*) as dall')
            ->selectRaw('max(disconnected_at) as last_at')
            ->orderBy($sort, $dir)
            ->orderBy('username')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachCustomers($rows->getCollection());
        $this->attachOnuReadings($rows->getCollection());

        return view('troubleshoot.analytics', [
            'rows' => $rows,
            'routers' => MikrotikRouter::orderBy('name')->get(['id', 'name']),
            'sort' => $sort,
            'dir' => $dir,
            'search' => $search,
            'routerId' => $routerId,
            'retentionDays' => $this->webhook->retentionDays(),
        ]);
    }

    private function routerFilter(Request $request): ?int
    {
        $id = (int) $request->query('router');

        return $id > 0 && MikrotikRouter::whereKey($id)->exists() ? $id : null;
    }

    /** Router id from the query, falling back to a saved default when `router` is absent. */
    private function resolvedRouterId(Request $request, array $saved): ?int
    {
        $id = $this->routerFilter($request);

        if ($id === null && ! $request->has('router') && ! empty($saved['router'])) {
            $id = MikrotikRouter::whereKey($saved['router'])->exists() ? (int) $saved['router'] : null;
        }

        return $id;
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
     * connection_id / mikrotik_username) so the tables can link to it.
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

    /**
     * Attach the most recent ONU receiving-power reading captured with a
     * disconnect for each username (see {@see PppUsageWebhookController}).
     *
     * @param  Collection<int, Model>  $rows
     */
    private function attachOnuReadings(Collection $rows): void
    {
        $names = $rows->pluck('username')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        // One row per username — the newest logged reading (id is monotonic
        // with disconnected_at) — instead of loading every historical event.
        $latest = PppUsageLog::query()
            ->whereIn('id', PppUsageLog::query()
                ->whereIn(DB::raw('lower(username)'), $names)
                ->whereNotNull('rx_power_dbm')
                ->groupBy(DB::raw('lower(username)'))
                ->selectRaw('max(id)'))
            ->get(['username', 'rx_power_dbm', 'tx_power_dbm', 'olt_onu_id', 'caller_id', 'disconnected_at'])
            ->keyBy(fn (PppUsageLog $log) => mb_strtolower(trim((string) $log->username)));

        $rows->each(function ($row) use ($latest): void {
            $reading = $latest->get(mb_strtolower(trim((string) $row->username)));
            $row->onu_rx_power = $reading?->rx_power_dbm;
            $row->onu_tx_power = $reading?->tx_power_dbm;
            $row->onu_rx_at = $reading?->disconnected_at;
            $row->onu_id = $reading?->olt_onu_id;
            $row->last_caller_id = $reading?->caller_id;
        });
    }

    /**
     * Attach every distinct device MAC each username connected from inside the
     * window, newest first, with how many events used it.
     *
     * @param  Collection<int, Model>  $rows
     */
    private function attachRecentMacs(Collection $rows, \DateTimeInterface $since, ?int $routerId): void
    {
        $names = $rows->pluck('username')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        $byUser = PppUsageLog::query()
            ->where('disconnected_at', '>=', $since)
            ->whereNotNull('caller_id')
            ->when($routerId, fn ($query) => $query->where('mikrotik_router_id', $routerId))
            ->whereIn(DB::raw('lower(username)'), $names)
            ->groupBy('username', 'caller_id')
            ->select('username', 'caller_id')
            ->selectRaw('max(disconnected_at) as seen_at')
            ->selectRaw('count(*) as hits')
            ->orderByDesc('seen_at')
            ->get()
            ->groupBy(fn ($row) => mb_strtolower(trim((string) $row->username)));

        $rows->each(function ($row) use ($byUser): void {
            $row->recent_macs = ($byUser->get(mb_strtolower(trim((string) $row->username))) ?? collect())
                ->map(fn ($entry) => [
                    'mac' => $entry->caller_id,
                    'seen_at' => $entry->seen_at,
                    'hits' => (int) $entry->hits,
                ])
                ->all();
        });
    }
}
