<?php

declare(strict_types=1);

namespace App\Core\Application\Views;

use App\Core\Domain\ValueObjects\Money;
use DateTimeImmutable;

final readonly class SaleView
{
    public string $currency;

    /**
     * @param  array<SaleItemView>  $items
     */
    public function __construct(
        public string $id,
        public DateTimeImmutable $soldAt,
        public string $soldBy,
        public Money $total,
        public array $items = [],
        ?string $currency = null
    ) {
        $this->currency = $currency ?? Money::DEFAULT_CURRENCY;
    }
}
