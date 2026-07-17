<?php

use App\Http\Middleware\EnsureUserHasPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => EnsureUserHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your login session expired. Reload the page and sign in again.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your login session expired. Please sign in again.'])
                ->withCookie(Cookie::forget(config('session.cookie')));
        });

        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Your login session expired. Reload the page and sign in again.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your login session expired. Please sign in again.'])
                ->withCookie(Cookie::forget(config('session.cookie')));
        });
    })->create();
