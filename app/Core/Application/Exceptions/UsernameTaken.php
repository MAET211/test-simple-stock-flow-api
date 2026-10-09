<?php

declare(strict_types=1);

namespace App\Core\Application\Exceptions;

use App\Core\Domain\Exceptions\BusinessRuleViolation;

final class UsernameTaken extends BusinessRuleViolation
{
    public function __construct(string $username)
    {
        parent::__construct("El usuario '{$username}' ya existe.");
    }
}
