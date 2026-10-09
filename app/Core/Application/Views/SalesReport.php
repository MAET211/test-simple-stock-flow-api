<?php

declare(strict_types=1);

namespace App\Core\Application\Views;

use App\Core\Domain\ValueObjects\Money;
use DateTimeImmutable;

final readonly class SalesReport
{
    public string $currency;

    /**
     * @param  array<SalesReportRow>  $rows
     */
    public function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
        public int $salesCount,
        public Money $grandTotal,
        public array $rows = [],
        ?string $currency = null
    ) {
        $this->currency = $currency ?? Money::DEFAULT_CURRENCY;
    }

    public static function empty(DateTimeImmutable $from, DateTimeImmutable $to): self
    {
        return new self(
            $from,
            $to,
            0,
            Money::zero(),
            [],
            Money::DEFAULT_CURRENCY
        );
    }
}
