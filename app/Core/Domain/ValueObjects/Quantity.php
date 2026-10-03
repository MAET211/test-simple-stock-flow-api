<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObjects;

use App\Core\Domain\Exceptions\InvalidQuantity;

/**
 * Units sold. Always greater than zero (RN-03).
 */
final class Quantity
{
    private function __construct(public readonly int $value) {}

    public static function of(int $value): self
    {
        if ($value <= 0) {
            throw new InvalidQuantity;
        }

        return new self($value);
    }
}
