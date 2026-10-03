<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class NegativeInitialStock extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('El stock inicial no puede ser negativo.');
    }
}
