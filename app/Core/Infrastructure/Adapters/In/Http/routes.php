<?php

declare(strict_types=1);

use App\Core\Infrastructure\Adapters\In\Http\Controllers\AuthController;
use App\Core\Infrastructure\Adapters\In\Http\Controllers\CategoryController;
use App\Core\Infrastructure\Adapters\In\Http\Controllers\HealthController;
use App\Core\Infrastructure\Adapters\In\Http\Controllers\MediaController;
use App\Core\Infrastructure\Adapters\In\Http\Controllers\ProductController;
use App\Core\Infrastructure\Adapters\In\Http\Controllers\SaleController;
use App\Core\Infrastructure\Adapters\In\Http\Controllers\SalesReportController;
use App\Core\Infrastructure\Adapters\In\Http\Middleware\AuthenticateJwtMiddleware;
use App\Core\Infrastructure\Adapters\In\Http\Middleware\ForceJsonMiddleware;
use App\Core\Infrastructure\Adapters\In\Http\Middleware\RequireRoleMiddleware;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/health', [HealthController::class, 'health']);
Route::get('/media/{key}', [MediaController::class, 'show']);

Route::middleware([ForceJsonMiddleware::class])->group(function (): void {
    Route::post('/api/auth/login', [AuthController::class, 'login']);

    // Protected API routes
    Route::middleware([AuthenticateJwtMiddleware::class])->group(function (): void {
        // Admin only: Register
        Route::post('/api/auth/register', [AuthController::class, 'register'])
            ->middleware(RequireRoleMiddleware::class.':admin');

        // Categories
        Route::get('/api/categories', [CategoryController::class, 'index']);

        // Products (read)
        Route::get('/api/products', [ProductController::class, 'index']);
        Route::get('/api/products/{id}', [ProductController::class, 'show']);

        // Products (write - admin only)
        Route::middleware([RequireRoleMiddleware::class.':admin'])->group(function (): void {
            Route::post('/api/products', [ProductController::class, 'store']);
            Route::put('/api/products/{id}', [ProductController::class, 'update']);
            Route::delete('/api/products/{id}', [ProductController::class, 'destroy']);
            Route::post('/api/products/{id}/image', [ProductController::class, 'uploadImage']);
        });

        // Sales
        Route::post('/api/sales', [SaleController::class, 'store']);
        Route::get('/api/sales', [SaleController::class, 'index']);
        Route::get('/api/sales/{id}', [SaleController::class, 'show']);

        // Reports
        Route::get('/api/reports/sales', [SalesReportController::class, 'index']);
    });
});
