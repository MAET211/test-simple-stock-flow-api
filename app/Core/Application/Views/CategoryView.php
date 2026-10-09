<?php

declare(strict_types=1);

namespace App\Core\Application\Views;

final readonly class CategoryView
{
    public function __construct(
        public string $id,
        public string $name
    ) {}
}
