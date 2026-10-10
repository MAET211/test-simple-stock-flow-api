<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Core\Infrastructure\Adapters\Out\Persistence\Models\CategoryModel;
use Illuminate\Http\UploadedFile;

require_once __DIR__.'/HttpTestCase.php';

test('GET /api/products without token returns 401 empty with WWW-Authenticate header', function (): void {
    $response = $this->getJson('/api/products');

    $response->assertStatus(401)
        ->assertHeader('WWW-Authenticate', 'Bearer');
    expect($response->getContent())->toBeEmpty();
});

test('GET /api/products with invalid token returns 401 empty with error="invalid_token"', function (): void {
    $response = $this->withHeader('Authorization', 'Bearer invalid.jwt.token')
        ->getJson('/api/products');

    $response->assertStatus(401)
        ->assertHeader('WWW-Authenticate', 'Bearer error="invalid_token"');
    expect($response->getContent())->toBeEmpty();
});

test('POST /api/products with admin token creates product with Location header', function (): void {
    $cat = CategoryModel::query()->first();
    expect($cat)->not->toBeNull();

    $token = generateTestToken('admin');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/products', [
            'name' => 'Martillo de Acero',
            'price' => 25000.50,
            'stock' => 10,
            'categoryId' => (string) $cat->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id'])
        ->assertHeader('Location');

    $id = $response->json('id');
    expect($response->headers->get('Location'))->toBe('/api/products/'.$id);
});

test('POST /api/products with seller token returns 403 empty', function (): void {
    $cat = CategoryModel::query()->first();
    $token = generateTestToken('seller');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/products', [
            'name' => 'Martillo',
            'price' => 25000,
            'stock' => 10,
            'categoryId' => (string) $cat->id,
        ]);

    $response->assertStatus(403);
    expect($response->getContent())->toBeEmpty();
});

test('POST /api/products with string price returns 400 with errors.price', function (): void {
    $cat = CategoryModel::query()->first();
    $token = generateTestToken('admin');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/products', [
            'name' => 'Martillo',
            'price' => '25000',
            'stock' => 10,
            'categoryId' => (string) $cat->id,
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'title' => 'Datos de entrada no válidos',
            'status' => 400,
            'errors' => [
                'price' => ['Expected number'],
            ],
        ]);
});

test('GET /api/products paginates and caps size', function (): void {
    $cat = CategoryModel::query()->first();
    $token = generateTestToken('admin');

    // Create a product
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/products', [
            'name' => 'Destornillador Phillips',
            'price' => 15000,
            'stock' => 5,
            'categoryId' => (string) $cat->id,
        ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/products?size=999');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'items',
            'page',
            'size',
            'total',
            'totalPages',
        ]);

    expect($response->json('size'))->toBe(100);
    expect($response->json('items.0.imageUrl'))->toBeNull();
    expect($response->json('items.0.currency'))->toBe('COP');
});

test('GET /api/products/no-es-uuid and PUT/DELETE return 404 empty', function (): void {
    $token = generateTestToken('admin');

    $getRes = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/products/no-es-uuid');
    expect($getRes->status())->toBe(404)
        ->and($getRes->getContent())->toBeEmpty();

    $putRes = $this->withHeader('Authorization', 'Bearer '.$token)->putJson('/api/products/no-es-uuid', [
        'name' => 'Test',
        'price' => 100,
        'stock' => 1,
        'categoryId' => CategoryModel::query()->first()->id,
    ]);
    expect($putRes->status())->toBe(404)
        ->and($putRes->getContent())->toBeEmpty();

    $delRes = $this->withHeader('Authorization', 'Bearer '.$token)->deleteJson('/api/products/no-es-uuid');
    expect($delRes->status())->toBe(404)
        ->and($delRes->getContent())->toBeEmpty();
});

test('POST /api/products/{id}/image uploads image and returns relative URL', function (): void {
    $cat = CategoryModel::query()->first();
    $token = generateTestToken('admin');

    $createRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/products', [
            'name' => 'Taladro Percutor',
            'price' => 150000,
            'stock' => 3,
            'categoryId' => (string) $cat->id,
        ]);
    $id = $createRes->json('id');

    $file = UploadedFile::fake()->create('taladro.jpg', 100, 'image/jpeg');

    $imgRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->post('/api/products/'.$id.'/image', [
            'file' => $file,
        ]);

    $imgRes->assertStatus(200)
        ->assertJsonStructure(['url']);

    expect($imgRes->json('url'))->toStartWith('/media/');
});

test('POST /api/products/{id}/image rejects disallowed MIME type with 422', function (): void {
    $cat = CategoryModel::query()->first();
    $token = generateTestToken('admin');

    $createRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/products', [
            'name' => 'Taladro',
            'price' => 150000,
            'stock' => 3,
            'categoryId' => (string) $cat->id,
        ]);
    $id = $createRes->json('id');

    $file = UploadedFile::fake()->create('animation.gif', 100, 'image/gif');

    $imgRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->post('/api/products/'.$id.'/image', [
            'file' => $file,
        ]);

    $imgRes->assertStatus(422)
        ->assertJson([
            'title' => 'Regla de negocio violada',
            'status' => 422,
            'detail' => 'Tipo de archivo no permitido: image/gif.',
        ]);
});
