<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

final class InsufficientStock extends BusinessRuleViolation
{
    public function __construct(string $productName, int $available, int $requested)
    {
        parent::__construct("Stock insuficiente para '{$productName}': disponible {$available}, solicitado {$requested}.");
    }
}
