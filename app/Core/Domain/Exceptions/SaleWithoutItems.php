<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class SaleWithoutItems extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('La venta debe tener al menos un ítem.');
    }
}
