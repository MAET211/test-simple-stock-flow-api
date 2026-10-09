<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Ports\Out\TokenGenerator;
use App\Core\Application\Views\AuthResult;
use App\Core\Domain\ValueObjects\UserId;
use DateTimeImmutable;
use DateTimeZone;

final class FakeTokenGenerator implements TokenGenerator
{
    public function generate(UserId $userId, string $username, string $role): AuthResult
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return new AuthResult(
            accessToken: 'fake.jwt.token.'.$username,
            expiresAt: $now->modify('+60 minutes'),
            username: $username,
            role: $role
        );
    }
}
