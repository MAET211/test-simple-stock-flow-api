<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

use App\Core\Application\Views\CategoryView;
use App\Core\Domain\Category;
use App\Core\Domain\ValueObjects\CategoryId;

interface CategoryRepository
{
    public function find(CategoryId $id): ?Category;

    /**
     * @param  array<CategoryId>  $ids
     * @return array<string, Category>
     */
    public function findByIds(array $ids): array;

    /**
     * @return array<CategoryView>
     */
    public function listAll(): array;
}
