<?php

declare(strict_types=1);

namespace App\Core\Application\Views;

use App\Core\Domain\ValueObjects\Money;

final readonly class ProductView
{
    public string $currency;

    public function __construct(
        public string $id,
        public string $name,
        public Money $price,
        public int $stock,
        public string $categoryId,
        public string $categoryName,
        public ?string $imageUrl = null,
        ?string $currency = null
    ) {
        $this->currency = $currency ?? Money::DEFAULT_CURRENCY;
    }
}
