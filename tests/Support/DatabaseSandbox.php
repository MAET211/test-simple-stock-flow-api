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

        $host = is_string($val = config('database.connections.mysql.host')) ? $val : '127.0.0.1';
        $port = is_string($val = config('database.connections.mysql.port')) ? $val : '3306';
        $rootPassword = is_string($val = config('database.connections.mysql.password')) ? $val : '';
        $database = is_string($val = config('database.connections.mysql.database')) ? $val : 'stockflow_test';

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

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");

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
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            DB::table('sale_item')->delete();
            DB::table('sale')->delete();
            DB::table('product')->delete();
            DB::table('user')->delete();

            if (DB::table('category')->count() === 0) {
                /** @var class-string $seederClass */
                $seederClass = 'App\\Core\\Infrastructure\\Adapters\\Out\\Persistence\\Seeders\\CategorySeeder';
                if (class_exists($seederClass)) {
                    Artisan::call('db:seed', [
                        '--class' => $seederClass,
                        '--force' => true,
                    ]);
                }
            }
        } catch (\Throwable) {
            // Tables may not exist in early test stages
        }
    }
}
