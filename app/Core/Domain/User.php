<?php

declare(strict_types=1);

namespace App\Core\Domain;

use App\Core\Domain\Exceptions\InvalidRole;
use App\Core\Domain\Exceptions\UsernameRequired;
use App\Core\Domain\ValueObjects\UserId;

/**
 * Identity aggregate root. The domain never sees the plain password: it receives the hash
 * that the PasswordHasher port produces.
 */
final class User
{
    private function __construct(
        private readonly UserId $id,
        private readonly string $username,
        private readonly string $passwordHash,
        private readonly string $role,
    ) {}

    public static function register(UserId $id, string $username, string $passwordHash, string $role): self
    {
        $normalized = self::normalizeUsername($username);

        if ($normalized === '') {
            throw new UsernameRequired;
        }

        if (trim($passwordHash) === '') {
            throw new \InvalidArgumentException('The password hash cannot be blank.');
        }

        if (! Roles::isValid($role)) {
            throw new InvalidRole($role);
        }

        return new self($id, $normalized, $passwordHash, $role);
    }

    /**
     * Rebuilds a stored user. Used by persistence mappers.
     */
    public static function reconstitute(UserId $id, string $username, string $passwordHash, string $role): self
    {
        return new self($id, $username, $passwordHash, $role);
    }

    public static function normalizeUsername(string $username): string
    {
        return mb_strtolower(trim($username), 'UTF-8');
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function role(): string
    {
        return $this->role;
    }
}
