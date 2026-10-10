<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Application\Ports\In\ProvisionAdmin;
use App\Core\Infrastructure\Adapters\Out\Persistence\Seeders\CategorySeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use stdClass;

final class StockflowBoot extends Command
{
    protected $signature = 'stockflow:boot';

    protected $description = 'Run migrations, seed default categories and provision administrator';

    public function handle(ProvisionAdmin $provisionAdmin): int
    {
        $requiredVars = [
            'APP_KEY',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'JWT_SIGNING_KEY',
            'ADMIN_EMAIL',
            'ADMIN_PASSWORD',
        ];

        foreach ($requiredVars as $var) {
            $val = getenv($var);
            if ($val === false || trim($val) === '') {
                $raw = $_ENV[$var] ?? $_SERVER[$var] ?? null;
                $val = is_string($raw) ? $raw : null;
            }
            if ($val === null || trim($val) === '') {
                $this->error("Missing required environment variable: {$var}");

                return 1;
            }
        }

        $this->info('Acquiring database boot lock...');
        $row = DB::selectOne("SELECT GET_LOCK('stockflow_boot', 60) as l");
        $lockAcquired = $row instanceof stdClass && (bool) $row->l;
        if (! $lockAcquired) {
            $this->error('Could not acquire database lock for stockflow:boot within 60 seconds.');

            return 1;
        }

        try {
            $this->info('Running migrations...');
            $migrationResult = Artisan::call('migrate', ['--force' => true]);
            $this->output->write(Artisan::output());
            if ($migrationResult !== 0) {
                return $migrationResult;
            }

            $this->info('Seeding categories...');
            $seedResult = Artisan::call('db:seed', [
                '--class' => CategorySeeder::class,
                '--force' => true,
            ]);
            $this->output->write(Artisan::output());
            if ($seedResult !== 0) {
                return $seedResult;
            }

            $this->info('Provisioning initial administrator...');
            $rawEmail = getenv('ADMIN_EMAIL');
            if ($rawEmail === false || $rawEmail === '') {
                $envVal = $_ENV['ADMIN_EMAIL'] ?? '';
                $rawEmail = is_string($envVal) ? $envVal : '';
            }
            $adminEmail = $rawEmail;

            $rawPass = getenv('ADMIN_PASSWORD');
            if ($rawPass === false || $rawPass === '') {
                $envVal = $_ENV['ADMIN_PASSWORD'] ?? '';
                $rawPass = is_string($envVal) ? $envVal : '';
            }
            $adminPassword = $rawPass;
            $provisionAdmin->ensureAdmin($adminEmail, $adminPassword);

            $this->info('StockFlow successfully booted.');

            return 0;
        } finally {
            DB::statement("SELECT RELEASE_LOCK('stockflow_boot')");
        }
    }
}
