<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function isValidPerPage(int $perPage, array $options, int $maxPerPage = 20000): bool
    {
        return in_array($perPage, $options, true) || ($perPage > 0 && $perPage <= $maxPerPage);
    }

    protected function perPage(Request $request, int $default = 50, array $options = [25, 50, 100, 200]): int
    {
        $maxPerPage = 20000;
        $sessionKey = 'per_page_default.'.($request->route()?->getName() ?: $request->path());
        $storedDefault = (int) $request->session()->get($sessionKey, $default);
        if ($this->isValidPerPage($storedDefault, $options, $maxPerPage)) {
            $default = $storedDefault;
        }

        $perPage = (int) $request->query('per_page', $default);
        if (! $this->isValidPerPage($perPage, $options, $maxPerPage)) {
            $perPage = $default;
        }

        if ($request->query('make_per_page_default') === '1' && $this->isValidPerPage($perPage, $options, $maxPerPage)) {
            $request->session()->put($sessionKey, $perPage);
        }

        return $perPage;
    }
}
