<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function perPage(Request $request, int $default = 50, array $options = [25, 50, 100, 200]): int
    {
        $perPage = (int) $request->query('per_page', $default);

        return in_array($perPage, $options, true) ? $perPage : $default;
    }
}
