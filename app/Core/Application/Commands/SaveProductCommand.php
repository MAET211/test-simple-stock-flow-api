<?php

declare(strict_types=1);

namespace App\Core\Application\Commands;

use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\Money;

final readonly class SaveProductCommand
{
    public function __construct(
        public string $name,
        public Money $price,
        public int $initialStock,
        public CategoryId $categoryId
    ) {}
}
