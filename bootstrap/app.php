<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['api', \App\Http\Middleware\UseDatabaseV2::class])
                ->prefix('api-v2')
                ->group(base_path('routes/api_v2.php'));

            Route::middleware(['api', \App\Http\Middleware\UseDatabaseV2::class])
                ->prefix('api/v2')
                ->group(base_path('routes/api_v2.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
