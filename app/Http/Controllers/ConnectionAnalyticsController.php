<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\PppUsageLog;
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
    /** List users whose disconnect count crosses a threshold within a window. */
    public function frequentDisconnects(Request $request)
    {
        $hours = max(1, min(8760, (int) $request->query('hours', 24)));
        $minCount = max(1, min(10000, (int) $request->query('min_count', 10)));
        $routerId = $this->routerFilter($request);
        $perPage = max(10, min(200, (int) $request->query('per_page', 50)));

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

        return view('troubleshoot.frequent_disconnects', [
            'rows' => $rows,
            'routers' => MikrotikRouter::orderBy('name')->get(['id', 'name']),
            'hours' => $hours,
            'minCount' => $minCount,
            'routerId' => $routerId,
            'since' => $since,
        ]);
    }

    /** One row per user with disconnect counts across 24h / 7d / 30d / all time. */
    public function index(Request $request)
    {
        $sortable = ['username', 'd24h', 'd7d', 'd30d', 'dall', 'last_at'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'dall';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));
        $routerId = $this->routerFilter($request);
        $perPage = max(10, min(200, (int) $request->query('per_page', 50)));

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

        return view('troubleshoot.analytics', [
            'rows' => $rows,
            'routers' => MikrotikRouter::orderBy('name')->get(['id', 'name']),
            'sort' => $sort,
            'dir' => $dir,
            'search' => $search,
            'routerId' => $routerId,
        ]);
    }

    private function routerFilter(Request $request): ?int
    {
        $id = (int) $request->query('router');

        return $id > 0 && MikrotikRouter::whereKey($id)->exists() ? $id : null;
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
}
