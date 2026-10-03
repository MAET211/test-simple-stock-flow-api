<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class InvalidQuantity extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('La cantidad debe ser mayor a cero.');
    }
}
