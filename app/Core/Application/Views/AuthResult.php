<?php

declare(strict_types=1);

namespace App\Core\Application\Views;

use DateTimeImmutable;

final readonly class AuthResult
{
    public function __construct(
        public string $accessToken,
        public DateTimeImmutable $expiresAt,
        public string $username,
        public string $role
    ) {}
}
