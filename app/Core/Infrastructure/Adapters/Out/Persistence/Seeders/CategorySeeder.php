<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('category')->upsert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'General'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Herramientas'],
            ['id' => '33333333-3333-4333-8333-333333333333', 'name' => 'Electricidad'],
            ['id' => '44444444-4444-4444-8444-444444444444', 'name' => 'Fontanería'],
            ['id' => '55555555-5555-4555-8555-555555555555', 'name' => 'Pinturas'],
        ], ['id'], ['name']);
    }
}
