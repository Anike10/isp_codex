<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerOnuPowerSample;
use App\Services\OnuPowerHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Troubleshoot → ONU Signal History. One page with the ONU Rx/Tx optical-power
 * graph for every party that has stored samples, plus a single Rx/Tx "Show"
 * toggle at the top that drives all of them (the same global preference the
 * party page uses).
 *
 * The date range below is only a VIEW window — it does not touch retention.
 * How long samples are kept before deletion is the separate "Keep history for"
 * setting on the OLT / ONU page.
 */
class OnuSignalHistoryController extends Controller
{
    /** dB swing across the window at or above which a party counts as "not stable". */
    private const DEFAULT_SWING_DB = 3.0;

    public function index(Request $request)
    {
        $history = app(OnuPowerHistoryService::class);
        $retentionDays = $history->retentionDays();
        $intervalHours = $history->intervalHours();
        $showRx = $history->showRx();
        $showTx = $history->showTx();

        [$from, $to] = $this->windowFromRequest($request);
        $search = trim((string) $request->query('q', ''));
        $unstableOnly = $request->query('stability') === 'unstable';
        $swing = max(0.0, (float) $request->query('swing', self::DEFAULT_SWING_DB));

        $perPageDefault = 20;
        $perPageOptions = [10, 20, 50, 100, 200];
        $perPage = $this->perPage($request, $perPageDefault, $perPageOptions);

        $parties = collect();
        $pagination = null;
        $unstableCount = 0;

        if (Schema::hasTable('customer_onu_power_samples')) {
            $unstableIds = $this->unstablePartyIds($from, $to, $swing);
            $unstableCount = $unstableIds->count();

            $paginator = Customer::query()
                ->whereHas('onuPowerSamples', fn ($q) => $q->whereBetween('sampled_at', [$from, $to]))
                ->when($search !== '', function ($q) use ($search) {
                    $like = '%'.$search.'%';
                    $q->where(function ($q) use ($like) {
                        $q->where('name', 'like', $like)
                            ->orWhere('connection_id', 'like', $like)
                            ->orWhere('mikrotik_username', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('last_connected_mac', 'like', $like);
                    });
                })
                ->when($unstableOnly, fn ($q) => $q->whereIn('id', $unstableIds))
                ->orderBy('name')
                ->paginate($perPage)
                ->withQueryString();

            $ids = $paginator->getCollection()->modelKeys();

            $samplesByParty = CustomerOnuPowerSample::query()
                ->whereIn('customer_id', $ids)
                ->whereBetween('sampled_at', [$from, $to])
                ->orderBy('sampled_at')
                ->get(['customer_id', 'rx_power_dbm', 'tx_power_dbm', 'status', 'sampled_at'])
                ->groupBy('customer_id');

            $unstableLookup = $unstableIds->flip();

            $paginator->getCollection()->transform(function (Customer $customer) use ($samplesByParty, $unstableLookup) {
                $customer->setRelation('onuSamplesWindow', $samplesByParty->get($customer->id, collect()));
                $customer->onu_unstable = $unstableLookup->has($customer->id);

                return $customer;
            });

            $parties = $paginator->getCollection();
            $pagination = $paginator;
        }

        return view('troubleshoot.onu_signal', compact(
            'parties', 'pagination', 'retentionDays', 'intervalHours', 'showRx', 'showTx',
            'perPage', 'perPageDefault', 'perPageOptions',
            'from', 'to', 'search', 'unstableOnly', 'swing', 'unstableCount'
        ));
    }

    /**
     * View window: [from 00:00, to 23:59:59]. Defaults to the last 7 days.
     * Bad or reversed input falls back / swaps rather than erroring.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function windowFromRequest(Request $request): array
    {
        $parse = static function (?string $value): ?Carbon {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        };

        $from = ($parse($request->query('from')) ?? Carbon::today()->subDays(7))->startOfDay();
        $to = ($parse($request->query('to')) ?? Carbon::today())->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * Parties whose Rx power over the window swings by >= $swing dB, or leaves
     * the -25..-15 dBm normal band, or reports more than one ONU status.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function unstablePartyIds(Carbon $from, Carbon $to, float $swing)
    {
        return CustomerOnuPowerSample::query()
            ->whereBetween('sampled_at', [$from, $to])
            ->whereNotNull('rx_power_dbm')
            ->groupBy('customer_id')
            ->havingRaw(
                '(MAX(rx_power_dbm) - MIN(rx_power_dbm)) >= ? OR MIN(rx_power_dbm) < -25 OR MAX(rx_power_dbm) > -15 OR COUNT(DISTINCT status) > 1',
                [$swing]
            )
            ->pluck('customer_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }
}
