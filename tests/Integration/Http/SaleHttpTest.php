<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Core\Infrastructure\Adapters\Out\Persistence\Models\CategoryModel;

require_once __DIR__.'/HttpTestCase.php';

test('POST /api/sales with lines empty returns 422', function (): void {
    $token = generateTestToken('seller');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/sales', [
            'lines' => [],
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'title' => 'Regla de negocio violada',
            'status' => 422,
            'detail' => 'La venta debe tener al menos un ítem.',
        ]);
});

test('POST /api/sales without lines returns 400 with errors.lines', function (): void {
    $token = generateTestToken('seller');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/sales', []);

    $response->assertStatus(400)
        ->assertJson([
            'title' => 'Datos de entrada no válidos',
            'status' => 400,
            'errors' => [
                'lines' => ['Field required'],
            ],
        ]);
});

test('POST /api/sales with duplicate products returns 422', function (): void {
    $cat = CategoryModel::query()->first();
    $adminToken = generateTestToken('admin');

    $createRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->postJson('/api/products', [
            'name' => 'Bombillo LED',
            'price' => 8000,
            'stock' => 10,
            'categoryId' => (string) $cat->id,
        ]);
    $prodId = $createRes->json('id');

    $sellerToken = generateTestToken('seller', 'vendedor1');

    $response = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
        ->postJson('/api/sales', [
            'lines' => [
                ['productId' => $prodId, 'quantity' => 1],
                ['productId' => $prodId, 'quantity' => 2],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'title' => 'Regla de negocio violada',
            'status' => 422,
            'detail' => 'La venta tiene productos repetidos.',
        ]);
});

test('POST /api/sales places sale, deducts stock, sets soldBy from token and announces Location', function (): void {
    $cat = CategoryModel::query()->first();
    $adminToken = generateTestToken('admin');

    $createRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->postJson('/api/products', [
            'name' => 'Cable Eléctrico 10m',
            'price' => 30000,
            'stock' => 5,
            'categoryId' => (string) $cat->id,
        ]);
    $prodId = $createRes->json('id');

    $sellerToken = generateTestToken('seller', 'carlos_vendedor');

    $saleRes = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
        ->postJson('/api/sales', [
            'lines' => [
                ['productId' => $prodId, 'quantity' => 2],
            ],
        ]);

    $saleRes->assertStatus(201)
        ->assertJsonStructure(['id'])
        ->assertHeader('Location');

    $saleId = $saleRes->json('id');
    expect($saleRes->headers->get('Location'))->toBe('/api/sales/'.$saleId);

    // Verify product stock decremented
    $prodRes = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
        ->getJson('/api/products/'.$prodId);
    expect($prodRes->json('stock'))->toBe(3);

    // Retrieve sale by ID
    $getSaleRes = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
        ->getJson('/api/sales/'.$saleId);

    $getSaleRes->assertStatus(200)
        ->assertJsonStructure([
            'id',
            'soldAt',
            'soldBy',
            'total',
            'currency',
            'items',
        ]);

    expect($getSaleRes->json('soldBy'))->toBe('carlos_vendedor')
        ->and($getSaleRes->json('total'))->toBe(60000)
        ->and($getSaleRes->json('currency'))->toBe('COP')
        ->and(count($getSaleRes->json('items')))->toBe(1);
});

test('GET /api/sales validates strict from and to parameters', function (): void {
    $token = generateTestToken('seller');

    $noParams = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/sales');
    $noParams->assertStatus(400)
        ->assertJson([
            'title' => 'Datos de entrada no válidos',
            'status' => 400,
            'errors' => [
                'from' => ['Field required'],
                'to' => ['Field required'],
            ],
        ]);

    $reversedRange = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/sales?from=2026-12-01T00:00:00Z&to=2026-01-01T00:00:00Z');
    $reversedRange->assertStatus(422)
        ->assertJson([
            'title' => 'Regla de negocio violada',
            'status' => 422,
            'detail' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

    $noOffset = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/sales?from=2026-01-01&to=2026-12-31');
    $noOffset->assertStatus(400);
});
