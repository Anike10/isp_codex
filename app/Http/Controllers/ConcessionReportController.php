<?php

namespace App\Http\Controllers;

use App\Models\ConcessionLog;
use App\Models\User;
use Illuminate\Http\Request;

class ConcessionReportController extends Controller
{
    /** Tab 1: every concession action, most recent first, with money value. */
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $base = ConcessionLog::query()
            ->action($filters['action_type'])
            ->byUser($filters['user_id'])
            ->between($filters['from'], $filters['to'])
            ->when($filters['customer_id'], fn ($q) => $q->where('customer_id', $filters['customer_id']));

        // Rows whose value is still growing (open force-active periods and
        // still-on special flags) so the totals reflect the give-away so far.
        $runningRows = (clone $base)
            ->where(fn ($q) => $q->where('value_status', 'pending')
                ->orWhere(fn ($q2) => $q2->where('action_type', 'mark_special')->whereNull('closed_at')))
            ->get(['id', 'action_type', 'created_at', 'closed_at', 'daily_rate', 'estimated_value', 'value_status']);

        $runningExtra = $runningRows->sum(
            fn (ConcessionLog $row) => max(0, $row->displayValue() - (float) $row->estimated_value),
        );

        $totals = [
            'count' => (clone $base)->count(),
            'value' => (float) (clone $base)->sum('estimated_value') + $runningExtra,
            'pending' => $runningRows->count(),
        ];

        $logs = $base
            ->with(['user', 'customer:id,name,connection_id,phone', 'package:id,name,monthly_price'])
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('concession_reports.index', [
            'logs' => $logs,
            'totals' => $totals,
            'filters' => $filters,
            'actionOptions' => ConcessionLog::ACTION_LABELS,
            'adminOptions' => $this->adminOptions(),
        ]);
    }

    /** Tab 2: per-admin, per-action count and total money given away. */
    public function summary(Request $request)
    {
        $filters = $this->filters($request);

        $summaryBase = ConcessionLog::query()
            ->between($filters['from'], $filters['to'])
            ->action($filters['action_type'])
            ->byUser($filters['user_id']);

        $rows = (clone $summaryBase)
            ->selectRaw('user_id, user_name, action_type, COUNT(*) as action_count, SUM(estimated_value) as total_value')
            ->groupBy('user_id', 'user_name', 'action_type')
            ->get();

        // Fold the still-growing give-away of open concessions into each bucket.
        // Every open row is already counted by the grouped query above, so the
        // matching (admin, action) bucket always exists.
        (clone $summaryBase)
            ->where(fn ($q) => $q->where('value_status', 'pending')
                ->orWhere(fn ($q2) => $q2->where('action_type', 'mark_special')->whereNull('closed_at')))
            ->get(['id', 'user_id', 'user_name', 'action_type', 'created_at', 'closed_at', 'daily_rate', 'estimated_value', 'value_status'])
            ->each(function (ConcessionLog $running) use ($rows): void {
                $extra = max(0, $running->displayValue() - (float) $running->estimated_value);
                if ($extra <= 0) {
                    return;
                }

                $bucket = $rows->first(fn ($row) => (int) $row->user_id === (int) $running->user_id
                    && $row->action_type === $running->action_type);

                if ($bucket) {
                    $bucket->total_value += $extra;
                }
            });

        $byAdmin = $rows
            ->groupBy(fn ($row) => $row->user_id ?: 'system')
            ->map(function ($group) {
                return [
                    'admin' => $group->first()->user_name ?: 'System',
                    'user_id' => $group->first()->user_id,
                    'actions' => $group->mapWithKeys(fn ($row) => [$row->action_type => [
                        'label' => ConcessionLog::ACTION_LABELS[$row->action_type] ?? $row->action_type,
                        'count' => (int) $row->action_count,
                        'value' => (float) $row->total_value,
                    ]])->all(),
                    'total_count' => (int) $group->sum('action_count'),
                    'total_value' => (float) $group->sum('total_value'),
                ];
            })
            ->sortByDesc('total_value')
            ->values();

        return view('concession_reports.summary', [
            'byAdmin' => $byAdmin,
            'filters' => $filters,
            'actionOptions' => ConcessionLog::ACTION_LABELS,
            'adminOptions' => $this->adminOptions(),
            'grandTotal' => (float) $rows->sum('total_value'),
            'grandCount' => (int) $rows->sum('action_count'),
        ]);
    }

    /**
     * @return array{action_type: ?string, user_id: ?int, customer_id: ?int, from: ?string, to: ?string}
     */
    private function filters(Request $request): array
    {
        $actionType = $request->string('action_type')->toString() ?: null;

        return [
            'action_type' => array_key_exists($actionType, ConcessionLog::ACTION_LABELS) ? $actionType : null,
            'user_id' => $request->integer('user_id') ?: null,
            'customer_id' => $request->integer('customer_id') ?: null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
        ];
    }

    private function adminOptions()
    {
        $ids = ConcessionLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id');

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }
}
