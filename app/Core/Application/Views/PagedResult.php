<?php

declare(strict_types=1);

namespace App\Core\Application\Views;

/**
 * @template T
 */
final readonly class PagedResult
{
    public int $totalPages;

    /**
     * @param  array<int, T>  $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $size,
        public int $total
    ) {
        $this->totalPages = $size <= 0 ? 0 : (int) ceil($total / $size);
    }
}
