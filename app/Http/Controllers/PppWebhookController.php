<?php

namespace App\Http\Controllers;

use App\Services\PppWebhookService;
use Illuminate\Http\Request;

class PppWebhookController extends Controller
{
    public function __construct(private readonly PppWebhookService $webhook) {}

    public function edit()
    {
        return view('troubleshoot.webhook', [
            'enabled' => $this->webhook->isEnabled(),
            'url' => $this->webhook->url(),
            'secret' => $this->webhook->secret(),
            'header' => PppWebhookService::SECRET_HEADER,
            'endpoint' => url('/api/ppp/usage'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'url' => ['required_if:enabled,1', 'nullable', 'url', 'max:2048'],
        ]);

        $enabled = (bool) ($data['enabled'] ?? false);
        $summary = $this->webhook->save($enabled, (string) ($data['url'] ?? ''));

        $state = $summary['enabled'] ? 'enabled' : 'disabled';
        $message = "Webhook tracking {$state}. on-down script "
            .($summary['enabled'] ? 'written to ' : 'cleared from ')
            ."{$summary['profiles']} PPP profile(s) on {$summary['routers']} router(s).";

        $skipped = collect($summary['results'])->filter(fn ($r) => isset($r['skipped']) || isset($r['error']));

        if ($skipped->isNotEmpty()) {
            return back()
                ->with('success', $message)
                ->with('warning', 'Not applied everywhere: '.$skipped->map(
                    fn ($r) => $r['router'].' — '.($r['error'] ?? $r['skipped'])
                )->implode(' | '));
        }

        return back()->with('success', $message);
    }
}
