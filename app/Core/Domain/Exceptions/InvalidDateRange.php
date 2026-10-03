<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class InvalidDateRange extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('La fecha final no puede ser anterior a la inicial.');
    }
}
