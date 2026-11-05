<?php

use App\Http\Middleware\CheckAppVersion;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // The mobile app's API (/api/v1/...): tokens, JSON only.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // tulabi.ly/{slug}: after every other route.
            Route::middleware('web')->group(base_path('routes/pages.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
            CheckAppVersion::class,
        ]);
        $middleware->throttleApi();

        $middleware->web(append: [
            EnsureAccountIsActive::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->routeIs('admin.*')
            ? route('admin.login')
            : route('login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->routeIs('admin.*')
            ? route('admin.dashboard')
            : route('account'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
