<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Ports\Out\PasswordHasher;

final class FakePasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return '$fake_argon2$'.$plain;
    }

    public function verify(string $plain, string $hash): bool
    {
        return $hash === '$fake_argon2$'.$plain;
    }
}
