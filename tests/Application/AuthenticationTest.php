<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Core\Application\Exceptions\AdminRoleNotAllowed;
use App\Core\Application\Exceptions\InvalidCredentials;
use App\Core\Application\Exceptions\UsernameTaken;
use App\Core\Application\Services\AdminProvisioningService;
use App\Core\Application\Services\AuthenticationService;
use App\Core\Domain\Exceptions\InvalidRole;
use App\Core\Domain\Roles;
use App\Core\Domain\User;
use App\Core\Domain\ValueObjects\UserId;
use Tests\Support\Fakes\FakePasswordHasher;
use Tests\Support\Fakes\FakeTokenGenerator;
use Tests\Support\Fakes\FakeUserRepository;

beforeEach(function (): void {
    $this->userRepo = new FakeUserRepository;
    $this->passwordHasher = new FakePasswordHasher;
    $this->tokenGenerator = new FakeTokenGenerator;

    $this->authService = new AuthenticationService(
        $this->userRepo,
        $this->passwordHasher,
        $this->tokenGenerator
    );

    $this->provisionService = new AdminProvisioningService(
        $this->userRepo,
        $this->passwordHasher
    );
});

test('login with valid credentials returns AuthResult with normalized username', function (): void {
    $hash = $this->passwordHasher->hash('Secret123!');
    $user = User::register(UserId::generate(), 'pedro', $hash, Roles::SELLER);
    $this->userRepo->add($user);

    $result = $this->authService->login('  PEDRO  ', 'Secret123!');

    expect($result->username)->toBe('pedro');
    expect($result->role)->toBe(Roles::SELLER);
    expect($result->accessToken)->toContain('pedro');
});

test('login with invalid username throws InvalidCredentials', function (): void {
    expect(fn () => $this->authService->login('nonexistent', 'Secret123!'))
        ->toThrow(InvalidCredentials::class, 'Usuario o contraseña incorrectos.');
});

test('login with incorrect password throws InvalidCredentials', function (): void {
    $hash = $this->passwordHasher->hash('Secret123!');
    $user = User::register(UserId::generate(), 'pedro', $hash, Roles::SELLER);
    $this->userRepo->add($user);

    expect(fn () => $this->authService->login('pedro', 'WrongPassword'))
        ->toThrow(InvalidCredentials::class, 'Usuario o contraseña incorrectos.');
});

test('register seller succeeds', function (): void {
    $id = $this->authService->register('laura', 'Secret123!', Roles::SELLER);

    $saved = $this->userRepo->findByUsername('laura');
    expect($saved)->not->toBeNull();
    expect($saved->id()->equals($id))->toBeTrue();
    expect($saved->role())->toBe(Roles::SELLER);
});

test('register admin throws AdminRoleNotAllowed', function (): void {
    expect(fn () => $this->authService->register('adminuser', 'Secret123!', Roles::ADMIN))
        ->toThrow(AdminRoleNotAllowed::class, 'Solo se pueden dar de alta vendedores. El administrador lo crea el despliegue.');
});

test('register duplicate username throws UsernameTaken', function (): void {
    $this->authService->register('laura', 'Secret123!', Roles::SELLER);

    expect(fn () => $this->authService->register('  LAURA  ', 'OtherSecret123!', Roles::SELLER))
        ->toThrow(UsernameTaken::class, "El usuario 'laura' ya existe.");
});

test('register with invalid role throws InvalidRole', function (): void {
    expect(fn () => $this->authService->register('laura', 'Secret123!', 'superuser'))
        ->toThrow(InvalidRole::class);
});

test('provision admin creates admin and is idempotent', function (): void {
    $this->provisionService->ensureAdmin('admin@stockflow.local', 'Admin123!');

    $admin = $this->userRepo->findByUsername('admin@stockflow.local');
    expect($admin)->not->toBeNull();
    expect($admin->role())->toBe(Roles::ADMIN);

    // Second call does nothing
    $this->provisionService->ensureAdmin('admin@stockflow.local', 'NewPassword!');
    expect($admin->passwordHash())->toBe('$fake_argon2$Admin123!');
});
