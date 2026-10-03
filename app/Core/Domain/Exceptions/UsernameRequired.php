<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class UsernameRequired extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('El nombre de usuario es obligatorio.');
    }
}
