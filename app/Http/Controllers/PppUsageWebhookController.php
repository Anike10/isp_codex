<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\OltOnu;
use App\Models\PppUsageLog;
use App\Services\PppWebhookService;
use App\Support\Mac;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives the JSON that a RouterOS PPP profile `on-down` script POSTs when a
 * session drops. See {@see PppWebhookService} for the script that produces it.
 */
class PppUsageWebhookController extends Controller
{
    public function __construct(private readonly PppWebhookService $webhook) {}

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
        $onu = $callerMac === null ? null : OltOnu::query()
            ->where(function ($query) use ($callerMac): void {
                $query->whereRaw('lower(mac_address) = ?', [$callerMac])
                    ->orWhere('learned_macs', 'like', '%"'.$callerMac.'"%');
            })
            ->orderByDesc('last_live_polled_at')
            ->first();

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

        $log = PppUsageLog::create([
            'mikrotik_router_id' => $router?->id,
            'customer_id' => $customer?->id,
            'olt_onu_id' => $onu?->id,
            'username' => $username,
            'caller_id' => $callerId ?: null,
            'reported_router_id' => $reportedRouterId,
            'uptime' => $data['uptime'] ?? null,
            'uptime_seconds' => isset($data['uptime']) ? $this->uptimeToSeconds($data['uptime']) : null,
            'download_bytes' => (int) round((float) ($data['download'] ?? 0)),
            'upload_bytes' => (int) round((float) ($data['upload'] ?? 0)),
            'rx_power_dbm' => $onu?->rx_power_dbm,
            'payload' => $request->all(),
            'disconnected_at' => now(),
        ]);

        return response()->json([
            'stored' => true,
            'id' => $log->id,
            'onu_id' => $onu?->id,
            'rx_power_dbm' => $onu?->rx_power_dbm,
        ], 201);
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
