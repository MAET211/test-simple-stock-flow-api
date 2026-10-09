<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Ports\Out\CategoryRepository;
use App\Core\Application\Views\CategoryView;
use App\Core\Domain\Category;
use App\Core\Domain\ValueObjects\CategoryId;

final class FakeCategoryRepository implements CategoryRepository
{
    /** @var array<string, Category> */
    private array $categories = [];

    public function add(Category $category): void
    {
        $this->categories[$category->id()->value()] = $category;
    }

    public function find(CategoryId $id): ?Category
    {
        return $this->categories[$id->value()] ?? null;
    }

    /**
     * @param  array<CategoryId>  $ids
     * @return array<string, Category>
     */
    public function findByIds(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if (isset($this->categories[$id->value()])) {
                $result[$id->value()] = $this->categories[$id->value()];
            }
        }

        return $result;
    }

    /**
     * @return array<CategoryView>
     */
    public function listAll(): array
    {
        $cats = array_values($this->categories);
        usort($cats, static fn (Category $a, Category $b): int => strcmp($a->name(), $b->name()));

        return array_map(
            static fn (Category $c): CategoryView => new CategoryView($c->id()->value(), $c->name()),
            $cats
        );
    }
}
