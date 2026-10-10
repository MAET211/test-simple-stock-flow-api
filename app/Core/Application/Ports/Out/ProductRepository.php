<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

use App\Core\Application\PageRequest;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\ProductView;
use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\ProductId;

interface ProductRepository
{
    public function find(ProductId $id): ?Product;

    /**
     * @return PagedResult<ProductView>
     */
    public function search(?string $search, ?CategoryId $categoryId, PageRequest $pageRequest): PagedResult;

    /**
     * @param  array<ProductId>  $ids
     * @return array<string, Product>
     */
    public function findActiveByIds(array $ids): array;

    public function add(Product $product): void;

    public function save(Product $product, bool $discontinue = false): void;
}
