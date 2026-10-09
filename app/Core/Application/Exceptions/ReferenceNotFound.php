<?php

declare(strict_types=1);

namespace App\Core\Application\Exceptions;

use App\Core\Domain\Exceptions\BusinessRuleViolation;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\ProductId;

final class ReferenceNotFound extends BusinessRuleViolation
{
    public static function category(CategoryId $categoryId): self
    {
        return new self("La categoría {$categoryId->value()} no existe.");
    }

    public static function product(ProductId $productId): self
    {
        return new self("El producto {$productId->value()} no existe.");
    }
}
