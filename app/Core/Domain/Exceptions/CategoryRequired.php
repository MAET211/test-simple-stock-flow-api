<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class CategoryRequired extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('La categoría es obligatoria.');
    }
}
