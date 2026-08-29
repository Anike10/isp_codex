<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Builds the timestamp + author prefix used on party (customer) `notes`
 * entries, e.g. "[29/08/2026 14:03 by Rakib] ...". The customer profile's
 * activity log parses this prefix to fill the "Admin" column.
 */
class PartyNote
{
    /** Prefix a note body with "[<now> by <actor>] ". */
    public static function stamp(string $body, ?string $actor = null): string
    {
        $actor = $actor ?: (Auth::user()?->name ?? 'System');

        return '['.now()->format('d/m/Y H:i').' by '.$actor.'] '.$body;
    }
}
