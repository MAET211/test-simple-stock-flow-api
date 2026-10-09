<?php

declare(strict_types=1);

namespace App\Core\Application\Commands;

use App\Core\Domain\ValueObjects\ProductId;

final readonly class AttachImageCommand
{
    public function __construct(
        public ProductId $productId,
        public string $bytes,
        public string $contentType
    ) {}
}
