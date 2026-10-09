<?php

declare(strict_types=1);

namespace App\Core\Application\Commands;

use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;

final readonly class PlaceSaleLineCommand
{
    public function __construct(
        public ProductId $productId,
        public Quantity $quantity
    ) {}
}
