<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 50);

        return in_array($perPage, [25, 50, 100, 200], true) ? $perPage : 50;
    }
}
