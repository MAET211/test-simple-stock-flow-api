<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

use App\Core\Application\Views\AuthResult;
use App\Core\Domain\ValueObjects\UserId;

interface TokenGenerator
{
    public function generate(UserId $userId, string $username, string $role): AuthResult;
}
