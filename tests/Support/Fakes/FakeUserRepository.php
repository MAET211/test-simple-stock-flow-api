<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Ports\Out\UserRepository;
use App\Core\Domain\User;

final class FakeUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function findByUsername(string $username): ?User
    {
        $normalized = User::normalizeUsername($username);

        return $this->users[$normalized] ?? null;
    }

    public function add(User $user): void
    {
        $this->users[$user->username()] = $user;
    }
}
