<?php

namespace App\Http\Controllers;

use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Services\MikrotikImportService;
use Illuminate\Http\Request;

class MikrotikImportController extends Controller
{
    public function importProfiles(MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $count = $service->importProfiles($mikrotikRouter);

        return back()->with('success', "Imported {$count} MikroTik PPP profiles. New profiles were added as zero-price packages.");
    }

    public function importIpPools(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $saveToApp = $request->boolean('save_to_app');
        $count = $service->importIpPools($mikrotikRouter, $saveToApp);

        if ($request->boolean('return_to_compare')) {
            $message = $saveToApp
                ? "Imported {$count} MikroTik IP pools to App IP Pools."
                : "Refreshed {$count} MikroTik IP pools.";

            return redirect()->to(route('mikrotik-routers.compare', $mikrotikRouter).'#ip-pools')
                ->with('success', $message);
        }

        return back()->with('success', "Imported {$count} MikroTik IP pools.");
    }

    public function importSecrets(MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $count = $service->importSecrets($mikrotikRouter);

        if (request()->boolean('return_to_compare')) {
            return back()->with('success', "Imported {$count} PPPoE secrets from {$mikrotikRouter->name}.");
        }

        return redirect()->route('mikrotik-routers.imported-secrets.index', $mikrotikRouter)
            ->with('success', "Imported {$count} PPPoE secrets from {$mikrotikRouter->name}.");
    }

    public function importActiveUsers(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $data = $request->validate([
            'active_password' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $breakdown = $service->importActiveUsers($mikrotikRouter, $data['active_password']);

        $message = "Active connections on {$mikrotikRouter->name}: {$breakdown['stored']} stored from {$breakdown['seen']} live session(s).";
        if ($breakdown['skipped_no_name'] > 0) {
            $message .= " {$breakdown['skipped_no_name']} had no username yet.";
        }
        if ($breakdown['duplicate_names'] > 0) {
            $message .= " {$breakdown['duplicate_names']} extra session(s) for a name already listed.";
        }

        if ($request->boolean('return_to_compare')) {
            return back()->with('success', $message);
        }

        return redirect()->route('mikrotik-routers.imported-secrets.index', $mikrotikRouter)
            ->with('success', $message.' Users without a real secret got the shared password.');
    }

    public function secrets(Request $request, MikrotikRouter $mikrotikRouter)
    {
        $sessionKey = 'per_page_default.mikrotik-imported-secrets';
        $perPage = max(1, min(500, (int) $request->query('per_page', $request->session()->get($sessionKey, 100))));
        if ($request->query('make_per_page_default') === '1') {
            $request->session()->put($sessionKey, $perPage);
        }
        $profileSessionKey = 'per_page_default.mikrotik-imported-profiles';
        $profilePerPage = max(1, min(500, (int) $request->query('profile_per_page', $request->session()->get($profileSessionKey, 25))));
        if ($request->query('make_profile_per_page_default') === '1') {
            $request->session()->put($profileSessionKey, $profilePerPage);
        }

        return view('mikrotik_routers.imported_secrets', [
            'mikrotikRouter' => $mikrotikRouter,
            'secrets' => $mikrotikRouter->importedSecrets()->with('customer')->orderBy('name')->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
            'profiles' => $mikrotikRouter->importedProfiles()->orderBy('name')->paginate($profilePerPage, ['*'], 'profile_page')->withQueryString(),
            'profilePerPage' => $profilePerPage,
            'pools' => $mikrotikRouter->importedIpPools()->orderBy('name')->get(),
        ]);
    }

    public function updateSecret(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportedSecret $mikrotikImportedSecret)
    {
        abort_unless($mikrotikImportedSecret->mikrotik_router_id === $mikrotikRouter->id, 404);
        $data = $request->validate([
            'router_comment' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'profile' => ['nullable', 'string', 'max:255'],
        ]);
        $mikrotikImportedSecret->update($data);

        return back()->with('success', 'Imported secret note updated.');
    }

    public function createParties(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $data = $request->validate([
            'secret_ids' => ['required', 'array', 'min:1'],
            'secret_ids.*' => ['integer'],
            'never_suspend' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $secrets = $mikrotikRouter->importedSecrets()->with('router')->whereIn('id', $data['secret_ids'])->get();

        $result = $service->createPartiesFromSecrets(
            $secrets,
            (bool) ($data['never_suspend'] ?? false),
            (bool) ($data['update_existing'] ?? false),
        );

        return back()->with('success', "Party import completed: {$result['created']} created, {$result['updated']} updated, {$result['skipped']} skipped.");
    }
}
