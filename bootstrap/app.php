<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(using: function (): void {
        Route::group([], base_path('app/Core/Infrastructure/Adapters/In/Http/routes.php'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        TrimStrings::skipWhen(static fn (): bool => true);
        ConvertEmptyStringsToNull::skipWhen(static fn (): bool => true);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A5 replaces this with the single ErrorHandler (architecture.md section 4).
    })
    ->create();
