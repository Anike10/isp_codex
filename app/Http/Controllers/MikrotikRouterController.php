<?php

namespace App\Http\Controllers;

use App\Models\MikrotikRouter;
use App\Services\MikrotikImportService;
use App\Services\RouterOsClient;
use App\Services\RouterOsConnectionDiagnostic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MikrotikRouterController extends Controller
{
    public function index(Request $request)
    {
        $routers = MikrotikRouter::query()
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('mikrotik_routers.index', compact('routers'));
    }

    public function create()
    {
        return view('mikrotik_routers.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'router_api_username' => $request->input('router_api_username', $request->input('username')),
            'router_api_password' => $request->input('router_api_password', $request->input('password')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip', 'max:45', 'unique:mikrotik_routers,ip_address'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'pppoe_sync_interval_minutes' => ['required', 'integer', 'min:60', 'max:1440', 'multiple_of:60'],
            'inactive_pppoe_profile' => ['required', 'string', 'max:255'],
            'router_api_username' => ['required', 'string', 'max:255'],
            'router_api_password' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);
        $data['username'] = $data['router_api_username'];
        $data['password'] = $data['router_api_password'];
        unset($data['router_api_username'], $data['router_api_password']);

        MikrotikRouter::create($data);

        return redirect()->route('mikrotik-routers.index')->with('success', 'MikroTik router added successfully.');
    }

    public function show(MikrotikRouter $mikrotikRouter, MikrotikImportService $importService)
    {
        $inactiveProfileExists = null;
        $inactiveProfileError = null;

        try {
            $inactiveProfile = trim((string) $mikrotikRouter->inactive_pppoe_profile);
            if ($inactiveProfile === '') {
                $inactiveProfileExists = false;
            } else {
                $inactiveProfileExists = $importService->hasPppProfile($mikrotikRouter, $inactiveProfile);
            }
        } catch (Throwable $exception) {
            $inactiveProfileExists = null;
            $inactiveProfileError = 'Could not verify the inactive profile on this MikroTik right now.';
        }

        return view('mikrotik_routers.show', compact('mikrotikRouter', 'inactiveProfileExists', 'inactiveProfileError'));
    }

    public function ensureInactivePppProfile(MikrotikRouter $mikrotikRouter, MikrotikImportService $importService)
    {
        try {
            $created = $importService->createPppProfile($mikrotikRouter, $mikrotikRouter->inactive_pppoe_profile);
            $importService->importProfiles($mikrotikRouter);

            if ($created) {
                return back()->with('success', "Inactive profile {$mikrotikRouter->inactive_pppoe_profile} created on {$mikrotikRouter->name}.");
            }

            return back()->with('success', "Inactive profile {$mikrotikRouter->inactive_pppoe_profile} already exists on {$mikrotikRouter->name}.");
        } catch (Throwable $exception) {
            return back()->with('error', 'Could not create inactive profile: '.$exception->getMessage());
        }
    }

    public function connectionStatus(MikrotikRouter $mikrotikRouter, RouterOsConnectionDiagnostic $diagnostic)
    {
        $checkedAt = now();

        if ($mikrotikRouter->status !== 'active') {
            $mikrotikRouter->update([
                'last_api_status' => 'inactive',
                'last_ping_status' => 'inactive',
                'api_status_since' => $mikrotikRouter->last_api_status === 'inactive' ? $mikrotikRouter->api_status_since : $checkedAt,
                'ping_status_since' => $mikrotikRouter->last_ping_status === 'inactive' ? $mikrotikRouter->ping_status_since : $checkedAt,
                'last_checked_at' => $checkedAt,
                'last_connection_message' => 'Router is marked inactive in this app.',
            ]);

            return response()->json([
                ...$this->routerStatusPayload($mikrotikRouter->refresh()),
                'message' => 'Router is marked inactive in this app.',
            ]);
        }

        $startedAt = microtime(true);
        $client = new RouterOsClient();
        $apiOnline = false;
        $apiMessage = null;
        $apiException = null;
        $apiLatency = null;

        try {
            $client->connect(
                $mikrotikRouter->ip_address,
                $mikrotikRouter->api_port,
                $mikrotikRouter->username,
                $mikrotikRouter->password,
                3
            );

            $apiOnline = true;
            $apiMessage = "RouterOS accepted the saved username '{$mikrotikRouter->username}'.";
            $apiLatency = (int) round((microtime(true) - $startedAt) * 1000);
        } catch (Throwable $exception) {
            $apiException = $exception;
            $apiLatency = (int) round((microtime(true) - $startedAt) * 1000);
        } finally {
            $client->close();
        }

        $ping = $this->pingHost($mikrotikRouter->ip_address);
        $apiDiagnostic = $apiOnline
            ? ['type' => 'connected', 'label' => 'Login successful', 'message' => $apiMessage, 'guidance' => null]
            : $diagnostic->describe($apiException, $mikrotikRouter, $ping['online']);
        $apiMessage = trim($apiDiagnostic['label'].': '.$apiDiagnostic['message'].' '.($apiDiagnostic['guidance'] ?? ''));

        $update = [
            'last_api_status' => $apiOnline ? 'online' : 'offline',
            'last_ping_status' => $ping['online'] ? 'online' : 'offline',
            'last_api_latency_ms' => $apiLatency,
            'last_ping_latency_ms' => $ping['latency_ms'],
            'last_checked_at' => $checkedAt,
            'last_connection_message' => $apiMessage,
        ];

        $apiStatus = $apiOnline ? 'online' : 'offline';
        $pingStatus = $ping['online'] ? 'online' : 'offline';

        if ($mikrotikRouter->last_api_status !== $apiStatus) {
            $update['api_status_since'] = $checkedAt;
        }

        if ($mikrotikRouter->last_ping_status !== $pingStatus) {
            $update['ping_status_since'] = $checkedAt;
        }

        if ($apiOnline && $mikrotikRouter->last_api_status !== 'online') {
            $update['last_online_at'] = $checkedAt;
        } elseif (! $apiOnline && $mikrotikRouter->last_api_status !== 'offline') {
            $update['last_offline_at'] = $checkedAt;
        }

        if ($ping['online'] && $mikrotikRouter->last_ping_status !== 'online') {
            $update['last_ping_at'] = $checkedAt;
        }

        $mikrotikRouter->update($update);

        return response()->json([
            ...$this->routerStatusPayload($mikrotikRouter->refresh()),
            'message' => $apiMessage,
            'ping_message' => $ping['message'],
            'diagnostic_type' => $apiDiagnostic['type'],
            'diagnostic_label' => $apiDiagnostic['label'],
            'guidance' => $apiDiagnostic['guidance'],
        ]);
    }

    public function toggleStatus(MikrotikRouter $mikrotikRouter)
    {
        $disabled = $mikrotikRouter->status === 'active';
        $checkedAt = now();
        $mikrotikRouter->update([
            'status' => $disabled ? 'inactive' : 'active',
            'last_api_status' => $disabled ? 'inactive' : 'checking',
            'last_ping_status' => $disabled ? 'inactive' : 'checking',
            'api_status_since' => $checkedAt,
            'ping_status_since' => $checkedAt,
            'last_checked_at' => $checkedAt,
            'last_connection_message' => $disabled
                ? 'Router is temporarily disabled in this app. Status checks and scheduled PPPoE sync are paused.'
                : 'Router re-enabled. Waiting for the next connection check.',
        ]);

        return back()->with('success', $disabled
            ? "MikroTik router {$mikrotikRouter->name} temporarily disabled."
            : "MikroTik router {$mikrotikRouter->name} enabled.");
    }

    private function routerStatusPayload(MikrotikRouter $router): array
    {
        return [
            'api_online' => $router->last_api_status === 'online',
            'ping_online' => $router->last_ping_status === 'online',
            'api_label' => ucfirst($router->last_api_status ?? 'unknown'),
            'ping_label' => ucfirst($router->last_ping_status ?? 'unknown'),
            'api_latency_ms' => $router->last_api_latency_ms,
            'ping_latency_ms' => $router->last_ping_latency_ms,
            'api_duration' => $this->durationSince($router->api_status_since),
            'ping_duration' => $this->durationSince($router->ping_status_since),
            'checked_at' => $router->last_checked_at?->format('Y-m-d H:i:s'),
            'last_online_at' => $router->last_online_at?->format('Y-m-d H:i:s'),
            'last_offline_at' => $router->last_offline_at?->format('Y-m-d H:i:s'),
            'last_ping_at' => $router->last_ping_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function durationSince($since): ?string
    {
        if (! $since) {
            return null;
        }

        $seconds = max(0, (int) $since->diffInSeconds(now()));

        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            return $minutes.'m '.($seconds % 60).'s';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            return $hours.'h '.($minutes % 60).'m';
        }

        $days = intdiv($hours, 24);

        return $days.'d '.($hours % 24).'h';
    }

    private function pingHost(string $host): array
    {
        $hostArg = escapeshellarg($host);
        $command = PHP_OS_FAMILY === 'Windows'
            ? "ping -n 1 -w 1000 {$hostArg}"
            : "ping -c 1 -W 1 {$hostArg}";

        $startedAt = microtime(true);
        exec($command, $output, $exitCode);
        $latency = (int) round((microtime(true) - $startedAt) * 1000);
        $text = implode(' ', $output);
        $matchedLatency = null;

        if (preg_match('/(?:time[=<]|Average = )([0-9]+(?:\.[0-9]+)?)\s*ms/i', $text, $match)) {
            $matchedLatency = (int) round((float) $match[1]);
        }

        return [
            'online' => $exitCode === 0,
            'latency_ms' => $matchedLatency ?? $latency,
            'message' => $exitCode === 0 ? 'Ping successful.' : 'Ping failed.',
        ];
    }

    public function edit(MikrotikRouter $mikrotikRouter)
    {
        return view('mikrotik_routers.edit', compact('mikrotikRouter'));
    }

    public function update(Request $request, MikrotikRouter $mikrotikRouter)
    {
        $request->merge([
            'router_api_username' => $request->input('router_api_username', $request->input('username')),
            'router_api_password' => $request->input('router_api_password', $request->input('password')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip', 'max:45', Rule::unique('mikrotik_routers', 'ip_address')->ignore($mikrotikRouter->id)],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'pppoe_sync_interval_minutes' => ['required', 'integer', 'min:60', 'max:1440', 'multiple_of:60'],
            'inactive_pppoe_profile' => ['required', 'string', 'max:255'],
            'router_api_username' => ['required', 'string', 'max:255'],
            'router_api_password' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        $data['username'] = $data['router_api_username'];
        unset($data['router_api_username']);

        if (blank($data['router_api_password'])) {
            unset($data['router_api_password']);
        } else {
            $data['password'] = $data['router_api_password'];
            unset($data['router_api_password']);
        }

        $mikrotikRouter->update($data);

        return redirect()->route('mikrotik-routers.show', $mikrotikRouter)->with('success', 'MikroTik router updated successfully.');
    }

    public function destroy(MikrotikRouter $mikrotikRouter)
    {
        $name = $mikrotikRouter->name;
        $mikrotikRouter->delete();

        return redirect()->route('mikrotik-routers.index')->with('success', "MikroTik router {$name} deleted. Linked parties were kept, but their MikroTik Target was cleared.");
    }
}
