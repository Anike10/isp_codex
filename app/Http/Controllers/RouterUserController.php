<?php

namespace App\Http\Controllers;

use App\Models\MikrotikImportedSecret;
use App\Services\MikrotikImportService;
use Illuminate\Http\Request;

class RouterUserController extends Controller
{
    public function __construct(private readonly MikrotikImportService $importService) {}

    /** Full-page list of PPPoE secrets found on routers but missing from the app. */
    public function index()
    {
        $groups = $this->importService->unmanagedSecrets()
            ->groupBy(fn (MikrotikImportedSecret $secret) => $secret->router?->name ?? 'Unassigned router')
            ->map(fn ($secrets) => $secrets->values());

        return view('router_users.index', [
            'groups' => $groups,
            'unmanagedCount' => $groups->flatten()->count(),
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
}
