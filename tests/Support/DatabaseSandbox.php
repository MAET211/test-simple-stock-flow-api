<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;

final class DatabaseSandbox
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $rootPassword = (string) env('DB_PASSWORD', '');
        $database = (string) config('database.connections.mysql.database', 'stockflow_test');

        if (! str_ends_with($database, '_test')) {
            throw new \RuntimeException("Refusing to initialize sandbox on non-test database: {$database}");
        }

        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            'root',
            $rootPassword,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );

        $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
        $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");

        Artisan::call('migrate', ['--force' => true]);

        /** @var class-string $seederClass */
        $seederClass = 'App\\Core\\Infrastructure\\Adapters\\Out\\Persistence\\Seeders\\CategorySeeder';
        if (class_exists($seederClass)) {
            Artisan::call('db:seed', [
                '--class' => $seederClass,
                '--force' => true,
            ]);
        }

        self::$initialized = true;
    }

    public static function resetInitialization(): void
    {
        self::$initialized = false;
    }

    public static function cleanTables(): void
    {
        try {
            DB::table('sale_item')->delete();
            DB::table('sale')->delete();
            DB::table('product')->delete();
            DB::table('user')->delete();
        } catch (\Throwable) {
            // Tables may not exist in early test stages
        }
    }
}
