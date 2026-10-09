<?php

declare(strict_types=1);

namespace App\Core\Application\Commands;

use App\Core\Domain\ValueObjects\UserId;

final readonly class PlaceSaleCommand
{
    /**
     * @param  array<PlaceSaleLineCommand>  $lines
     */
    public function __construct(
        public UserId $soldByUserId,
        public string $soldByUsername,
        public array $lines
    ) {}
}
