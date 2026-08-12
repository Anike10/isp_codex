<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Http\Request;

class PrintContextService
{
    public function for(Request $request): array
    {
        $organizations = Organization::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $selectedOrganization = $organizations->firstWhere('id', (int) $request->query('organization_id'))
            ?? $organizations->firstWhere('is_default', true)
            ?? $organizations->first();

        if (! $selectedOrganization) {
            $selectedOrganization = Organization::query()
                ->where('is_default', true)
                ->orderBy('name')
                ->first();

            if (! $selectedOrganization) {
                $selectedOrganization = Organization::query()->create([
                    'name' => (string) config('app.name', 'Ultimate Solution'),
                    'is_active' => true,
                    'is_default' => true,
                ]);
            }
        }

        $organizations = Organization::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return compact('organizations', 'selectedOrganization');
    }
}
