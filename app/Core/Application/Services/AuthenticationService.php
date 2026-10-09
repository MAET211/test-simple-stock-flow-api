<?php

declare(strict_types=1);

namespace App\Core\Application\Services;

use App\Core\Application\Exceptions\AdminRoleNotAllowed;
use App\Core\Application\Exceptions\InvalidCredentials;
use App\Core\Application\Exceptions\UsernameTaken;
use App\Core\Application\Ports\In\Authenticate;
use App\Core\Application\Ports\Out\PasswordHasher;
use App\Core\Application\Ports\Out\TokenGenerator;
use App\Core\Application\Ports\Out\UserRepository;
use App\Core\Application\Views\AuthResult;
use App\Core\Domain\Exceptions\InvalidRole;
use App\Core\Domain\Roles;
use App\Core\Domain\User;
use App\Core\Domain\ValueObjects\UserId;

final readonly class AuthenticationService implements Authenticate
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private TokenGenerator $tokenGenerator
    ) {}

    public function login(string $username, string $password): AuthResult
    {
        $normalized = User::normalizeUsername($username);
        $user = $this->userRepository->findByUsername($normalized);

        if ($user === null || ! $this->passwordHasher->verify($password, $user->passwordHash())) {
            throw new InvalidCredentials('Usuario o contraseña incorrectos.');
        }

        return $this->tokenGenerator->generate($user->id(), $user->username(), $user->role());
    }

    public function register(string $username, string $password, string $role): UserId
    {
        if (trim($role) === Roles::ADMIN) {
            throw new AdminRoleNotAllowed('Solo se pueden dar de alta vendedores. El administrador lo crea el despliegue.');
        }

        $normalized = User::normalizeUsername($username);

        $existing = $this->userRepository->findByUsername($normalized);
        if ($existing !== null) {
            throw new UsernameTaken($normalized);
        }

        if (! Roles::isValid($role)) {
            throw new InvalidRole($role);
        }

        $hash = $this->passwordHasher->hash($password);
        $user = User::register(UserId::generate(), $normalized, $hash, $role);

        $this->userRepository->add($user);

        return $user->id();
    }
}
