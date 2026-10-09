<?php

declare(strict_types=1);

namespace App\Core\Application\Views;

use App\Core\Domain\ValueObjects\Money;

final readonly class SalesReportRow
{
    public function __construct(
        public string $productId,
        public string $productName,
        public string $categoryName,
        public int $unitsSold,
        public Money $revenue
    ) {}
}
