<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! collect($permissions)->contains(fn (string $permission): bool => $user->hasPermission($permission))) {
            abort(403, 'You do not have permission to access this page.');
        }

        $routeName = $request->route()?->getName();
        $menuKey = $routeName ? $this->menuKeyForRoute($routeName) : null;

        if ($menuKey && ! $user->canAccessMenu($menuKey)) {
            abort(403, 'You do not have permission to access this menu.');
        }

        return $next($request);
    }

    private function menuKeyForRoute(string $routeName): ?string
    {
        $items = collect(config('user_access.menu_groups', []))->pluck('items')->collapse();

        foreach ($items as $key => $item) {
            if (in_array($routeName, $item['routes'] ?? [], true)) {
                return (string) $key;
            }
        }

        foreach ($items as $key => $item) {
            if (collect($item['routes'] ?? [])->contains(fn (string $pattern): bool => Str::is($pattern, $routeName))) {
                return (string) $key;
            }
        }

        return null;
    }
}
