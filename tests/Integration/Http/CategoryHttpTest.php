<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__.'/HttpTestCase.php';

test('GET /api/categories with token returns 200 flat array of 5 categories ordered by name', function (): void {
    $token = generateTestToken('seller');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/categories');

    $response->assertStatus(200);

    $data = $response->json();
    expect($data)->toBeArray()
        ->and(count($data))->toBe(5);

    $names = array_column($data, 'name');
    expect($names)->toBe([
        'Electricidad',
        'Fontanería',
        'General',
        'Herramientas',
        'Pinturas',
    ]);
});
