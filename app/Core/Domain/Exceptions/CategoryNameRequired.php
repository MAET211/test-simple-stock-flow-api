<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class CategoryNameRequired extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('El nombre de la categoría es obligatorio.');
    }
}
