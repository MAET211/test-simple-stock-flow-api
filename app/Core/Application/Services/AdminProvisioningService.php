<?php

declare(strict_types=1);

namespace App\Core\Application\Services;

use App\Core\Application\Ports\In\ProvisionAdmin;
use App\Core\Application\Ports\Out\PasswordHasher;
use App\Core\Application\Ports\Out\UserRepository;
use App\Core\Domain\Roles;
use App\Core\Domain\User;
use App\Core\Domain\ValueObjects\UserId;

final readonly class AdminProvisioningService implements ProvisionAdmin
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher
    ) {}

    public function ensureAdmin(string $username, string $password): void
    {
        $normalized = User::normalizeUsername($username);

        $existing = $this->userRepository->findByUsername($normalized);
        if ($existing !== null) {
            return;
        }

        $hash = $this->passwordHasher->hash($password);
        $user = User::register(UserId::generate(), $normalized, $hash, Roles::ADMIN);

        $this->userRepository->add($user);
    }
}
