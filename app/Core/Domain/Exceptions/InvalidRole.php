<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class InvalidRole extends BusinessRuleViolation
{
    public function __construct(string $role)
    {
        parent::__construct("Rol no válido: '{$role}'.");
    }
}
