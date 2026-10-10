<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Security;

use App\Core\Application\Ports\Out\PasswordHasher;

final class Argon2PasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_ARGON2ID);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
