<?php

namespace App\Support;

use App\Models\OltOnu;
use Illuminate\Support\Facades\DB;

/**
 * Resolve device MACs (a party's `last_connected_mac`) to the OLT ONU they
 * belong to — matched on the ONU serial (`mac_address`) or one of its
 * learned MACs, the same rule the PPP disconnect webhook uses.
 */
final class OnuMatcher
{
    /**
     * @param  iterable<int, string|null>  $macs
     * @return array<string, OltOnu>  keyed by lowercased MAC
     */
    public static function byMac(iterable $macs): array
    {
        $macs = collect($macs)
            ->map(fn ($mac) => mb_strtolower(trim((string) $mac)))
            ->filter()
            ->unique()
            ->values();

        if ($macs->isEmpty()) {
            return [];
        }

        $onus = OltOnu::query()
            ->select('id', 'olt_name', 'pon_port', 'onu_id', 'onu_type', 'mac_address', 'learned_macs', 'port_vlans', 'rx_power_dbm', 'tx_power_dbm', 'status', 'name', 'last_live_polled_at')
            ->where(function ($query) use ($macs): void {
                $query->whereIn(DB::raw('lower(mac_address)'), $macs->all());
                foreach ($macs as $mac) {
                    $query->orWhere('learned_macs', 'like', '%"'.$mac.'"%');
                }
            })
            ->orderByRaw('last_live_polled_at is null')
            ->orderByDesc('last_live_polled_at')
            ->get();

        $byMac = [];
        foreach ($onus as $onu) {
            $keys = [mb_strtolower(trim((string) $onu->mac_address))];
            foreach ((array) $onu->learned_macs as $entry) {
                $keys[] = mb_strtolower(trim((string) (is_array($entry) ? ($entry['mac'] ?? '') : $entry)));
            }
            foreach (array_filter($keys) as $key) {
                $byMac[$key] ??= $onu;
            }
        }

        return $byMac;
    }

    /** The VLAN to show for a party on a matched ONU: the learned-MAC entry's VLAN, else the ONU's distinct port VLANs. */
    public static function vlanFor(OltOnu $onu, ?string $mac): ?string
    {
        $mac = mb_strtolower(trim((string) $mac));

        foreach ((array) $onu->learned_macs as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            if (mb_strtolower(trim((string) ($entry['mac'] ?? ''))) === $mac && trim((string) ($entry['vlan'] ?? '')) !== '') {
                return trim((string) $entry['vlan']);
            }
        }

        $portVlans = collect((array) $onu->port_vlans)
            ->pluck('vlan')->map(fn ($v) => trim((string) $v))->filter()->unique()->values();

        return $portVlans->isEmpty() ? null : $portVlans->implode(', ');
    }
}
