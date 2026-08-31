<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerOnuPowerSample;
use App\Services\OnuPowerHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Troubleshoot → ONU Signal History. One page with the ONU Rx/Tx optical-power
 * graph for every party that has stored samples, plus a single Rx/Tx "Show"
 * toggle at the top that drives all of them (the same global preference the
 * party page uses).
 */
class OnuSignalHistoryController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request)
    {
        $history = app(OnuPowerHistoryService::class);
        $days = $history->retentionDays();
        $intervalHours = $history->intervalHours();
        $showRx = $history->showRx();
        $showTx = $history->showTx();

        $parties = collect();
        $pagination = null;

        if (Schema::hasTable('customer_onu_power_samples')) {
            $since = now()->subDays($days);

            $paginator = Customer::query()
                ->whereHas('onuPowerSamples', fn ($q) => $q->where('sampled_at', '>=', $since))
                ->orderBy('name')
                ->paginate(self::PER_PAGE)
                ->withQueryString();

            $ids = $paginator->getCollection()->modelKeys();

            $samplesByParty = CustomerOnuPowerSample::query()
                ->whereIn('customer_id', $ids)
                ->where('sampled_at', '>=', $since)
                ->orderBy('sampled_at')
                ->get(['customer_id', 'rx_power_dbm', 'tx_power_dbm', 'status', 'sampled_at'])
                ->groupBy('customer_id');

            $paginator->getCollection()->transform(function (Customer $customer) use ($samplesByParty) {
                $customer->setRelation('onuSamplesWindow', $samplesByParty->get($customer->id, collect()));

                return $customer;
            });

            $parties = $paginator->getCollection();
            $pagination = $paginator;
        }

        return view('troubleshoot.onu_signal', compact(
            'parties', 'pagination', 'days', 'intervalHours', 'showRx', 'showTx'
        ));
    }
}
