<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\DatabaseSandbox;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $database = config('database.connections.mysql.database');
        if (! is_string($database) || ! str_ends_with($database, '_test')) {
            throw new \RuntimeException("Refusing to run integration tests against non-test database: {$database}");
        }

        DatabaseSandbox::init();
    }

    protected function tearDown(): void
    {
        DatabaseSandbox::cleanTables();

        parent::tearDown();
    }
}
