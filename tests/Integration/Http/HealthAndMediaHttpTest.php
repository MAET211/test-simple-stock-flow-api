<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Core\Infrastructure\Adapters\Out\Storage\LocalFileStorage;

require_once __DIR__.'/HttpTestCase.php';

test('GET /health returns 200 {"status":"ok"}', function (): void {
    $response = $this->get('/health');

    $response->assertStatus(200)
        ->assertExactJson(['status' => 'ok']);
});

test('GET /media/{key} with non-existent key returns 404 empty', function (): void {
    $response = $this->get('/media/0123456789abcdef0123456789abcdef.jpg');

    $response->assertStatus(404);
    expect($response->getContent())->toBeEmpty();
});

test('GET /media/{key} with existing image returns 200 with bytes and correct content-type', function (): void {
    /** @var LocalFileStorage $storage */
    $storage = app(LocalFileStorage::class);

    $key = $storage->save('fake-binary-jpeg-data', 'image/jpeg');

    $response = $this->get('/media/'.$key);

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'image/jpeg');

    expect($response->getContent())->toBe('fake-binary-jpeg-data');

    // Clean up
    $storage->delete($key);
});
