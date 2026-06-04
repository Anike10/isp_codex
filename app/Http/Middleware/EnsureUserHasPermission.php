<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! collect($permissions)->contains(fn (string $permission): bool => $user->hasPermission($permission))) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
