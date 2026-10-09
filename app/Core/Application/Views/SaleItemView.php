<?php

declare(strict_types=1);

namespace App\Core\Application\Views;

use App\Core\Domain\ValueObjects\Money;

final readonly class SaleItemView
{
    public Money $subtotal;

    public function __construct(
        public string $productId,
        public string $productName,
        public string $categoryName,
        public int $quantity,
        public Money $unitPrice,
        ?Money $subtotal = null
    ) {
        $this->subtotal = $subtotal ?? $this->unitPrice->multiply($this->quantity);
    }
}
