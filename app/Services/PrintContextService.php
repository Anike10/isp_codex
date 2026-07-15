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

        abort_unless($selectedOrganization, 503, 'No active organization is configured for printing.');

        return compact('organizations', 'selectedOrganization');
    }
}
