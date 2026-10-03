<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class PriceMustBePositive extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('El precio debe ser mayor a cero.');
    }
}
