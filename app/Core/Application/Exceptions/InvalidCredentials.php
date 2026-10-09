<?php

declare(strict_types=1);

namespace App\Core\Application\Exceptions;

use App\Core\Domain\Exceptions\BusinessRuleViolation;

final class InvalidCredentials extends BusinessRuleViolation
{
    public function __construct(string $message = 'Usuario o contraseña incorrectos.')
    {
        parent::__construct($message);
    }
}
