<?php

declare(strict_types=1);

namespace App\Core\Application\Exceptions;

use App\Core\Domain\Exceptions\BusinessRuleViolation;

final class AdminRoleNotAllowed extends BusinessRuleViolation
{
    public function __construct(string $message = 'Solo se pueden dar de alta vendedores. El administrador lo crea el despliegue.')
    {
        parent::__construct($message);
    }
}
