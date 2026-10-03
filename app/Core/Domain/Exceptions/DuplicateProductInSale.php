<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class DuplicateProductInSale extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('La venta tiene productos repetidos.');
    }
}
