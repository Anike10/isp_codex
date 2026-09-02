<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\PppUsageLog;
use App\Services\PppSessionSnapshotService;
use App\Services\PppWebhookService;
use App\Support\Mac;
use App\Support\OnuMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives the JSON that a RouterOS PPP profile `on-down` script POSTs when a
 * session drops. See {@see PppWebhookService} for the script that produces it.
 */
class PppUsageWebhookController extends Controller
{
    public function __construct(
        private readonly PppWebhookService $webhook,
        private readonly PppSessionSnapshotService $snapshots,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $expected = $this->webhook->secret();
        if ($expected !== '' && ! hash_equals($expected, (string) $request->header(PppWebhookService::SECRET_HEADER))) {
            return response()->json(['message' => 'Invalid webhook secret.'], 401);
        }

        $data = $request->validate([
            'user' => ['required', 'string', 'max:255'],
            'uptime' => ['nullable', 'string', 'max:64'],
            'download' => ['nullable', 'numeric'],
            'upload' => ['nullable', 'numeric'],
            'caller_id' => ['nullable', 'string', 'max:64'],
            'router_id' => ['nullable', 'string', 'max:64'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $username = trim($data['user']);
        $callerId = trim((string) ($data['caller_id'] ?? ''));
        $callerMac = Mac::colon($callerId);
        $reportedRouterId = isset($data['router_id']) ? trim((string) $data['router_id']) : null;

        $router = $reportedRouterId !== null && $reportedRouterId !== ''
            ? MikrotikRouter::find((int) $reportedRouterId)
            : null;

        // Match the OLT ONU by the client MAC — against its serial ("Serial /
        // MAC") or any address it has learned ("Device MACs").
        $onu = $callerMac === null ? null : (OnuMatcher::byMac([$callerMac])[$callerMac] ?? null);

        $customer = Customer::query()
            ->when($router, fn ($query) => $query->where(function ($q) use ($router): void {
                $q->where('mikrotik_router_id', $router->id)->orWhereNull('mikrotik_router_id');
            }))
            ->where(function ($query) use ($username): void {
                $query->whereRaw('lower(connection_id) = ?', [mb_strtolower($username)])
                    ->orWhereRaw('lower(mikrotik_username) = ?', [mb_strtolower($username)]);
            })
            ->orderByRaw('mikrotik_router_id is null')
            ->first();

        // Fall back to the last MAC we saw this party connect from. The OR pair
        // is wrapped so the soft-delete scope still applies to both branches.
        if (! $customer && $callerMac !== null) {
            $customer = Customer::query()
                ->where(function ($query) use ($callerMac, $callerId): void {
                    $query->whereRaw('lower(last_connected_mac) = ?', [$callerMac])
                        ->orWhere('last_connected_mac', $callerId);
                })
                ->first();
        }

        // The disconnect webhook is an independent, authenticated source of
        // the party's latest device MAC. Persist it here as well as in the log
        // so ONU matching and signal-history capture keep working even when a
        // router's API credential is temporarily unavailable.
        if ($customer && $callerMac !== null) {
            $customer->forceFill([
                'last_connected_mac' => strtoupper($callerMac),
                'last_connected_at' => now(),
            ])->save();
        }

        $attributes = [
            'mikrotik_router_id' => $router?->id,
            'customer_id' => $customer?->id,
            'olt_onu_id' => $onu?->id,
            'username' => $username,
            'caller_id' => $callerId ?: null,
            'disconnect_reason' => $this->cleanReason($data['reason'] ?? null),
            'source' => 'webhook',
            'reported_router_id' => $reportedRouterId,
            'uptime' => $data['uptime'] ?? null,
            'uptime_seconds' => isset($data['uptime']) ? $this->uptimeToSeconds($data['uptime']) : null,
            'download_bytes' => (int) round((float) ($data['download'] ?? 0)),
            'upload_bytes' => (int) round((float) ($data['upload'] ?? 0)),
            'rx_power_dbm' => $onu?->rx_power_dbm,
            'tx_power_dbm' => $onu?->tx_power_dbm,
            'payload' => $request->all(),
            'disconnected_at' => now(),
        ];

        // The API listener (or a manual snapshot reconciliation) may notice
        // the vanished session just before a delayed on-down request arrives.
        // Enrich that collector row instead of inserting the disconnect twice.
        $log = $router ? $this->snapshots->recentSnapshotLog($router, $username) : null;
        if ($log) {
            $payload = is_array($log->payload) ? $log->payload : [];
            $payload['webhook'] = $request->all();
            $collector = str_contains((string) $log->source, 'listener') ? 'listener' : 'snapshot';

            $log->forceFill([
                'customer_id' => $customer?->id ?? $log->customer_id,
                'olt_onu_id' => $onu?->id ?? $log->olt_onu_id,
                'caller_id' => $callerId ?: $log->caller_id,
                'disconnect_reason' => $attributes['disconnect_reason'] ?? $log->disconnect_reason,
                'source' => 'webhook+'.$collector,
                'reported_router_id' => $reportedRouterId ?? $log->reported_router_id,
                'rx_power_dbm' => $onu?->rx_power_dbm ?? $log->rx_power_dbm,
                'tx_power_dbm' => $onu?->tx_power_dbm ?? $log->tx_power_dbm,
                'payload' => $payload,
            ])->save();
        } else {
            $log = PppUsageLog::create($attributes);
        }

        return response()->json([
            'stored' => true,
            'id' => $log->id,
            'onu_id' => $onu?->id,
            'rx_power_dbm' => $onu?->rx_power_dbm,
            'tx_power_dbm' => $onu?->tx_power_dbm,
        ], 201);
    }

    /**
     * RouterOS PPP $"last-disconnect-reason" as a short, trimmed string.
     * Empty / whitespace-only values (a session that ended with no reason)
     * become null so the reports can show a clean dash.
     */
    private function cleanReason(mixed $reason): ?string
    {
        $reason = trim(preg_replace('/\s+/', ' ', (string) $reason));

        return $reason === '' ? null : mb_substr($reason, 0, 250);
    }

    /**
     * RouterOS uptime strings: "6w2d5h10m3s", "3m20s", "45s", and the
     * "1d02:03:04" / "02:03:04" clock forms.
     */
    private function uptimeToSeconds(string $uptime): int
    {
        $uptime = trim(mb_strtolower($uptime));
        if ($uptime === '') {
            return 0;
        }

        $seconds = 0;
        $units = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];

        if (preg_match_all('/(\d+)\s*([wdhms])/', $uptime, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $seconds += (int) $match[1] * $units[$match[2]];
            }

            return $seconds;
        }

        // Fallback for the "1d02:03:04" / "02:03:04" clock forms only.
        if (preg_match('/(?:(\d+)d)?(\d{1,2}):(\d{2}):(\d{2})/', $uptime, $clock)) {
            $seconds += (int) ($clock[1] ?: 0) * 86400
                + (int) $clock[2] * 3600
                + (int) $clock[3] * 60
                + (int) $clock[4];
        }

        return $seconds;
    }
}
