<?php

declare(strict_types=1);

use App\Core\Infrastructure\Adapters\In\Http\Errors\ErrorHandler;
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
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(static fn (Throwable $e) => ErrorHandler::render($e));
    })
    ->create();
