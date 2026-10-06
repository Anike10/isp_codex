<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Response;

class InactivePortalController extends Controller
{
    public function __invoke(): Response
    {
        $organization = Organization::defaultOrganization();
        $numbers = $organization?->pleaseCallNumbers() ?? [];

        return response()
            ->view('inactive_portal.show', compact('organization', 'numbers'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
