<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class PortBindingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // A4 and following steps bind interfaces to adapters here.
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Core/Infrastructure/Adapters/Out/Persistence/Migrations'));
    }
}
