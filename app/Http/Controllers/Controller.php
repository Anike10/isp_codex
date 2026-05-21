<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function perPage(Request $request, int $default = 50, array $options = [25, 50, 100, 200]): int
    {
        $sessionKey = 'per_page_default.'.($request->route()?->getName() ?: $request->path());
        $storedDefault = (int) $request->session()->get($sessionKey, $default);
        $default = in_array($storedDefault, $options, true) ? $storedDefault : $default;
        $perPage = (int) $request->query('per_page', $default);

        if (! in_array($perPage, $options, true)) {
            $perPage = $default;
        }

        if ($request->query('make_per_page_default') === '1') {
            $request->session()->put($sessionKey, $perPage);
        }

        return $perPage;
    }
}
