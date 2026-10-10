<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

test('boot creates schema and leaves 5 categories on empty database', function (): void {
    $dbName = (string) config('database.connections.mysql.database');
    DB::statement('DROP DATABASE IF EXISTS `'.$dbName.'`');
    DB::statement('CREATE DATABASE `'.$dbName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
    DB::statement('USE `'.$dbName.'`');

    $exitCode = Artisan::call('stockflow:boot');
    expect($exitCode)->toBe(0);

    $categories = DB::table('category')->orderBy('id')->get();
    expect($categories)->toHaveCount(5);

    $expected = [
        '11111111-1111-4111-8111-111111111111' => 'General',
        '22222222-2222-4222-8222-222222222222' => 'Herramientas',
        '33333333-3333-4333-8333-333333333333' => 'Electricidad',
        '44444444-4444-4444-8444-444444444444' => 'Fontanería',
        '55555555-5555-4555-8555-555555555555' => 'Pinturas',
    ];

    foreach ($expected as $id => $name) {
        $cat = $categories->firstWhere('id', $id);
        expect($cat)->not->toBeNull()
            ->and($cat->name)->toBe($name);
    }
});

test('boot is idempotent: second execution leaves 5 categories and 5 migrations', function (): void {
    Artisan::call('stockflow:boot');
    Artisan::call('stockflow:boot');

    expect(DB::table('category')->count())->toBe(5)
        ->and(DB::table('migrations')->count())->toBe(5);
});

test('boot migrate:status marks all five migrations as executed', function (): void {
    Artisan::call('migrate:status');
    $output = Artisan::output();

    expect($output)->toContain('create_category_table')
        ->and($output)->toContain('create_user_table')
        ->and($output)->toContain('create_product_table')
        ->and($output)->toContain('create_sale_table')
        ->and($output)->toContain('create_sale_item_table')
        ->and(DB::table('migrations')->count())->toBe(5);
});

test('boot fresh migrations produce identical schema hash', function (): void {
    $computeSchemaHash = function (): string {
        $cols = DB::table('information_schema.columns')
            ->select('TABLE_NAME as table_name', 'COLUMN_NAME as column_name', 'COLUMN_TYPE as column_type', 'IS_NULLABLE as is_nullable', 'COLLATION_NAME as collation_name')
            ->where('TABLE_SCHEMA', 'stockflow_test')
            ->where('TABLE_NAME', '<>', 'migrations')
            ->orderBy('TABLE_NAME')
            ->orderBy('ORDINAL_POSITION')
            ->get();

        return hash('sha256', serialize($cols));
    };

    Artisan::call('migrate:fresh', ['--force' => true]);
    $hash1 = $computeSchemaHash();

    Artisan::call('migrate:fresh', ['--force' => true]);
    $hash2 = $computeSchemaHash();

    expect($hash1)->toBe($hash2);

    Artisan::call('stockflow:boot');
});

test('boot fails and names missing env var when mandatory variable is absent', function (string $missingVar): void {
    $origEnv = $_ENV[$missingVar] ?? null;
    $origServer = $_SERVER[$missingVar] ?? null;
    $origGetEnv = getenv($missingVar);
    try {
        putenv($missingVar); // Unset
        unset($_ENV[$missingVar], $_SERVER[$missingVar]);

        $exitCode = Artisan::call('stockflow:boot');
        $output = Artisan::output();

        expect($exitCode)->not->toBe(0)
            ->and($output)->toContain($missingVar);
    } finally {
        if ($origGetEnv !== false) {
            putenv("{$missingVar}={$origGetEnv}");
        }
        if ($origEnv !== null) {
            $_ENV[$missingVar] = $origEnv;
        }
        if ($origServer !== null) {
            $_SERVER[$missingVar] = $origServer;
        }
    }
})->with([
    'APP_KEY',
    'DB_HOST',
    'ADMIN_EMAIL',
    'ADMIN_PASSWORD',
]);
