<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Application\Ports\In\Authenticate;
use App\Core\Application\Ports\In\GetSales;
use App\Core\Application\Ports\In\GetSalesReport;
use App\Core\Application\Ports\In\ManageProducts;
use App\Core\Application\Ports\In\PlaceSale;
use App\Core\Application\Ports\In\ProvisionAdmin;
use App\Core\Application\Ports\Out\CategoryRepository;
use App\Core\Application\Ports\Out\Clock;
use App\Core\Application\Ports\Out\FileStorage;
use App\Core\Application\Ports\Out\PasswordHasher;
use App\Core\Application\Ports\Out\ProductRepository;
use App\Core\Application\Ports\Out\SaleRepository;
use App\Core\Application\Ports\Out\SalesReportQuery;
use App\Core\Application\Ports\Out\TokenGenerator;
use App\Core\Application\Ports\Out\UnitOfWork;
use App\Core\Application\Ports\Out\UserRepository;
use App\Core\Application\Services\AdminProvisioningService;
use App\Core\Application\Services\AuthenticationService;
use App\Core\Application\Services\GetSalesService;
use App\Core\Application\Services\PlaceSaleService;
use App\Core\Application\Services\ProductCatalogService;
use App\Core\Application\Services\SalesReportService;
use App\Core\Infrastructure\Adapters\Out\Clock\SystemClock;
use App\Core\Infrastructure\Adapters\Out\Persistence\EloquentUnitOfWork;
use App\Core\Infrastructure\Adapters\Out\Persistence\Repositories\EloquentCategoryRepository;
use App\Core\Infrastructure\Adapters\Out\Persistence\Repositories\EloquentProductRepository;
use App\Core\Infrastructure\Adapters\Out\Persistence\Repositories\EloquentSaleRepository;
use App\Core\Infrastructure\Adapters\Out\Persistence\Repositories\EloquentUserRepository;
use App\Core\Infrastructure\Adapters\Out\Persistence\SalesReportSqlQuery;
use App\Core\Infrastructure\Adapters\Out\Security\Argon2PasswordHasher;
use App\Core\Infrastructure\Adapters\Out\Security\JwtTokenGenerator;
use App\Core\Infrastructure\Adapters\Out\Storage\LocalFileStorage;
use Illuminate\Support\ServiceProvider;

final class PortBindingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Outbound Ports
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(PasswordHasher::class, Argon2PasswordHasher::class);
        $this->app->singleton(TokenGenerator::class, JwtTokenGenerator::class);
        $this->app->singleton(FileStorage::class, LocalFileStorage::class);
        $this->app->singleton(UnitOfWork::class, EloquentUnitOfWork::class);
        $this->app->singleton(CategoryRepository::class, EloquentCategoryRepository::class);
        $this->app->singleton(UserRepository::class, EloquentUserRepository::class);
        $this->app->singleton(ProductRepository::class, EloquentProductRepository::class);
        $this->app->singleton(SaleRepository::class, EloquentSaleRepository::class);
        $this->app->singleton(SalesReportQuery::class, SalesReportSqlQuery::class);

        // Inbound Ports / Services
        $this->app->singleton(PlaceSale::class, PlaceSaleService::class);
        $this->app->singleton(ManageProducts::class, ProductCatalogService::class);
        $this->app->singleton(GetSales::class, GetSalesService::class);
        $this->app->singleton(GetSalesReport::class, SalesReportService::class);
        $this->app->singleton(Authenticate::class, AuthenticationService::class);
        $this->app->singleton(ProvisionAdmin::class, AdminProvisioningService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Core/Infrastructure/Adapters/Out/Persistence/Migrations'));
    }
}
