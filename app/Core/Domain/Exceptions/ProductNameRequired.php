<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class ProductNameRequired extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('El nombre del producto es obligatorio.');
    }
}
