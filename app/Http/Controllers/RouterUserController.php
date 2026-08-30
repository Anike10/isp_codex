<?php

namespace App\Http\Controllers;

use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Services\MikrotikImportService;
use Illuminate\Http\Request;
use Throwable;

class RouterUserController extends Controller
{
    public function __construct(private readonly MikrotikImportService $importService) {}

    /** Full-page list of every imported PPPoE secret, matched parties marked. */
    public function index(Request $request)
    {
        $routers = MikrotikRouter::orderBy('name')->get(['id', 'name']);

        $routerId = $request->integer('router') ?: null;
        if ($routerId && ! $routers->contains('id', $routerId)) {
            $routerId = null;
        }

        $secrets = $this->importService->importedSecretsOverview($routerId);

        $groups = $secrets
            ->groupBy(fn (MikrotikImportedSecret $secret) => $secret->router?->name ?? 'Unassigned router')
            ->map(fn ($group) => $group->values());

        return view('router_users.index', [
            'groups' => $groups,
            'routers' => $routers,
            'selectedRouterId' => $routerId,
            'totalCount' => $secrets->count(),
            'unmanagedCount' => $secrets->where('is_unmanaged', true)->count(),
            'matchedCount' => $secrets->where('is_unmanaged', false)->count(),
            'lastCheckedAt' => MikrotikImportedSecret::max('imported_at'),
        ]);
    }

    /** Re-pull secrets from every active router now. */
    public function refresh(Request $request)
    {
        $summary = $this->importService->refreshActiveRouterSecrets();

        $message = "Refreshed router users: {$summary['imported']} secret(s) read from ".count($summary['results']).' router(s).';
        $errors = collect($summary['results'])->filter(fn ($r) => isset($r['error']));

        $redirect = $request->input('redirect_to') === 'dashboard'
            ? redirect()->route('dashboard')
            : redirect()->route('router-users.index');

        if ($errors->isNotEmpty()) {
            return $redirect
                ->with('success', $message)
                ->with('warning', 'Some routers failed: '.$errors->map(fn ($r) => $r['router'].' — '.$r['error'])->implode(' | '));
        }

        return $redirect->with('success', $message);
    }

    /** Pull /ppp/active connections from every active router, using one shared password. */
    public function refreshActive(Request $request)
    {
        $data = $request->validate([
            'active_password' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $summary = $this->importService->refreshActiveRouterConnections($data['active_password']);

        $message = "Pulled active connections: {$summary['imported']} stored from {$summary['seen']} live session(s) across "
            .count($summary['results']).' router(s).';
        if ($summary['skipped'] > 0) {
            $message .= " {$summary['skipped']} skipped (no username yet, or an extra session for a name already listed).";
        }
        $message .= ' Users without a real secret got the shared password.';
        $errors = collect($summary['results'])->filter(fn ($r) => isset($r['error']));

        $redirect = $request->input('redirect_to') === 'dashboard'
            ? redirect()->route('dashboard')
            : redirect()->route('router-users.index');

        if ($errors->isNotEmpty()) {
            return $redirect
                ->with('success', $message)
                ->with('warning', 'Some routers failed: '.$errors->map(fn ($r) => $r['router'].' — '.$r['error'])->implode(' | '));
        }

        return $redirect->with('success', $message);
    }

    /** Create app parties from the selected unmanaged secrets (may span routers). */
    public function import(Request $request)
    {
        $data = $request->validate([
            'secret_ids' => ['required', 'array', 'min:1'],
            'secret_ids.*' => ['integer'],
            'never_suspend' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        // Only allow secrets that are genuinely still unmanaged.
        $secrets = $this->importService->unmanagedSecretsQuery()
            ->with('router')
            ->whereIn('id', $data['secret_ids'])
            ->get();

        if ($secrets->isEmpty()) {
            return back()->with('warning', 'No matching unmanaged router users were found — the list may have changed.');
        }

        $result = $this->importService->createPartiesFromSecrets(
            $secrets,
            (bool) ($data['never_suspend'] ?? false),
            (bool) ($data['update_existing'] ?? false),
        );

        $redirect = $request->input('redirect_to') === 'dashboard'
            ? redirect()->route('dashboard')
            : redirect()->route('router-users.index');

        return $redirect->with('success', "Added parties from router users: {$result['created']} created, {$result['updated']} updated, {$result['skipped']} skipped.");
    }

    /**
     * Delete one router-only PPPoE secret from its MikroTik (and the local
     * imported row). Only rows that are genuinely not in the app can go —
     * anything still linked to, or name-matching, a party must be handled by
     * editing that party.
     */
    public function destroySecret(Request $request, MikrotikImportedSecret $secret)
    {
        $secret->loadMissing('router');
        $name = $secret->name;

        if (! $this->importService->unmanagedSecretsQuery()->whereKey($secret->id)->exists()) {
            return back()->with('warning', "\"{$name}\" is linked to a party — rename or delete that party instead.");
        }

        if ($secret->router?->read_only) {
            return back()->with('warning', "\"{$name}\" is on a read-only router ({$secret->router->name}). Remove it on the MikroTik directly, then Refresh secrets.");
        }

        try {
            $result = $this->importService->forgetImportedSecret($secret);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', "Could not remove \"{$name}\" from the router: ".$exception->getMessage());
        }

        $message = $result['router_removed'] > 0
            ? "Removed \"{$name}\" from MikroTik."
            : "Removed \"{$name}\" from the app list (no matching secret was on the router).";
        if ($result['sessions_closed'] > 0) {
            $message .= " {$result['sessions_closed']} live session(s) closed.";
        }

        return back()->with('success', $message);
    }
}
