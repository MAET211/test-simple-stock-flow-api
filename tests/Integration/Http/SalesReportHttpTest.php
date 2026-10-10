<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Core\Infrastructure\Adapters\Out\Persistence\Models\CategoryModel;

require_once __DIR__.'/HttpTestCase.php';

test('GET /api/reports/sales on empty range returns empty report with COP currency and rows []', function (): void {
    $token = generateTestToken('seller');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/reports/sales?from=2026-01-01T00:00:00Z&to=2026-02-01T00:00:00Z');

    $response->assertStatus(200)
        ->assertJson([
            'from' => '2026-01-01T00:00:00+00:00',
            'to' => '2026-02-01T00:00:00+00:00',
            'salesCount' => 0,
            'grandTotal' => 0,
            'currency' => 'COP',
            'rows' => [],
        ]);
});

test('GET /api/reports/sales aggregates correctly with ADR-006 latest product name', function (): void {
    $cat = CategoryModel::query()->first();
    $adminToken = generateTestToken('admin');
    $sellerToken = generateTestToken('seller', 'vendedor_ana');

    // Create product
    $createRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->postJson('/api/products', [
            'name' => 'Brocha 2 Pulgadas',
            'price' => 12000,
            'stock' => 10,
            'categoryId' => (string) $cat->id,
        ]);
    $prodId = $createRes->json('id');

    // Make sale
    $this->withHeader('Authorization', 'Bearer '.$sellerToken)
        ->postJson('/api/sales', [
            'lines' => [
                ['productId' => $prodId, 'quantity' => 3],
            ],
        ]);

    // Rename product in catalog
    $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->putJson('/api/products/'.$prodId, [
            'name' => 'Brocha 2 Pulgadas Profesional',
            'price' => 14000,
            'stock' => 7,
            'categoryId' => (string) $cat->id,
        ]);

    // Query sales report
    $reportRes = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
        ->getJson('/api/reports/sales?from=2020-01-01T00:00:00Z&to=2030-01-01T00:00:00Z');

    $reportRes->assertStatus(200);

    expect($reportRes->json('salesCount'))->toBe(1)
        ->and($reportRes->json('grandTotal'))->toBe(36000)
        ->and($reportRes->json('currency'))->toBe('COP')
        ->and(count($reportRes->json('rows')))->toBe(1);

    // Frozen name on sale was 'Brocha 2 Pulgadas'
    $row = $reportRes->json('rows.0');
    expect($row['productId'])->toBe($prodId)
        ->and($row['productName'])->toBe('Brocha 2 Pulgadas')
        ->and($row['unitsSold'])->toBe(3)
        ->and($row['revenue'])->toBe(36000);
});
