<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\In;

use App\Core\Application\Commands\AttachImageCommand;
use App\Core\Application\Commands\SaveProductCommand;
use App\Core\Application\PageRequest;
use App\Core\Application\Views\CategoryView;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\ProductView;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\ProductId;

interface ManageProducts
{
    public function create(SaveProductCommand $command): ProductId;

    public function update(ProductId $id, SaveProductCommand $command): void;

    public function discontinue(ProductId $id): void;

    public function get(ProductId $id): ProductView;

    /**
     * @return PagedResult<ProductView>
     */
    public function list(?string $search, ?CategoryId $categoryId, PageRequest $pageRequest): PagedResult;

    public function attachImage(AttachImageCommand $command): string;

    /**
     * @return array<CategoryView>
     */
    public function listCategories(): array;
}
