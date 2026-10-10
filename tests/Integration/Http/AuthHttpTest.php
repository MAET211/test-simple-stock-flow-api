<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Core\Application\Ports\In\ProvisionAdmin;

require_once __DIR__.'/HttpTestCase.php';

test('POST /api/auth/login with valid credentials returns 200 with AuthResult', function (): void {
    /** @var ProvisionAdmin $provisioner */
    $provisioner = app(ProvisionAdmin::class);
    $provisioner->ensureAdmin('admin@example.com', 'AdminPass123!');

    $response = $this->postJson('/api/auth/login', [
        'username' => '  ADMIN@example.com  ',
        'password' => 'AdminPass123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'accessToken',
            'expiresAt',
            'username',
            'role',
        ]);

    expect($response->json('username'))->toBe('admin@example.com')
        ->and($response->json('role'))->toBe('admin');
});

test('POST /api/auth/login with incorrect password returns 422', function (): void {
    /** @var ProvisionAdmin $provisioner */
    $provisioner = app(ProvisionAdmin::class);
    $provisioner->ensureAdmin('admin@example.com', 'AdminPass123!');

    $response = $this->postJson('/api/auth/login', [
        'username' => 'admin@example.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertStatus(422)
        ->assertHeader('Content-Type', 'application/problem+json; charset=utf-8')
        ->assertJson([
            'title' => 'Regla de negocio violada',
            'status' => 422,
            'detail' => 'Usuario o contraseña incorrectos.',
        ]);
});

test('POST /api/auth/login missing fields returns 400 with detail in spanish and errors', function (): void {
    $response = $this->postJson('/api/auth/login', [
        'username' => 'admin@example.com',
    ]);

    $response->assertStatus(400)
        ->assertHeader('Content-Type', 'application/problem+json; charset=utf-8')
        ->assertJson([
            'title' => 'Datos de entrada no válidos',
            'status' => 400,
            'detail' => 'Datos de entrada no válidos: password.',
            'errors' => [
                'password' => ['Field required'],
            ],
        ]);
});

test('GET /api/auth/login returns 405 empty with Allow header', function (): void {
    $response = $this->get('/api/auth/login');

    $response->assertStatus(405)
        ->assertHeader('Allow');
    expect($response->getContent())->toBeEmpty();
});

test('POST /api/auth/register without token returns 401 empty', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'username' => 'seller1',
        'password' => 'Pass12345!',
        'role' => 'seller',
    ]);

    $response->assertStatus(401)
        ->assertHeader('WWW-Authenticate', 'Bearer');
    expect($response->getContent())->toBeEmpty();
});

test('POST /api/auth/register with seller token returns 403 empty', function (): void {
    $token = generateTestToken('seller', 'seller_user');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/register', [
            'username' => 'seller2',
            'password' => 'Pass12345!',
            'role' => 'seller',
        ]);

    $response->assertStatus(403);
    expect($response->getContent())->toBeEmpty();
});

test('POST /api/auth/register with admin token and role admin returns 422 with DP-04 message', function (): void {
    $token = generateTestToken('admin', 'admin_user');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/register', [
            'username' => 'new_admin',
            'password' => 'Pass12345!',
            'role' => 'admin',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'title' => 'Regla de negocio violada',
            'status' => 422,
            'detail' => 'Solo se pueden dar de alta vendedores. El administrador lo crea el despliegue.',
        ]);
});

test('POST /api/auth/register with admin token creates seller and does not announce Location', function (): void {
    $token = generateTestToken('admin', 'admin_user');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/register', [
            'username' => 'vendedor_juan',
            'password' => 'Pass12345!',
            'role' => 'seller',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id']);

    expect($response->headers->has('Location'))->toBeFalse();
});
