<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Repositories;

use App\Core\Application\Ports\Out\CategoryRepository;
use App\Core\Application\Views\CategoryView;
use App\Core\Domain\Category;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\CategoryModel;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function find(CategoryId $id): ?Category
    {
        return $this->findById($id);
    }

    public function findById(CategoryId $id): ?Category
    {
        /** @var CategoryModel|null $model */
        $model = CategoryModel::query()->find($id->value());
        if ($model === null) {
            return null;
        }

        return Category::create(CategoryId::fromString((string) $model->id), (string) $model->name);
    }

    /**
     * @param  array<CategoryId>  $ids
     * @return array<string, Category>
     */
    public function findByIds(array $ids): array
    {
        $stringIds = array_map(static fn (CategoryId $id): string => $id->value(), $ids);
        /** @var Collection<int, CategoryModel> $models */
        $models = CategoryModel::query()->whereIn('id', $stringIds)->get();

        $result = [];
        foreach ($models as $model) {
            $cat = Category::create(CategoryId::fromString((string) $model->id), (string) $model->name);
            $result[$cat->id()->value()] = $cat;
        }

        return $result;
    }

    /**
     * @return array<CategoryView>
     */
    public function listAll(): array
    {
        /** @var Collection<int, CategoryModel> $models */
        $models = CategoryModel::query()->orderBy('name', 'asc')->get();

        return $models->map(
            static fn (CategoryModel $m): CategoryView => new CategoryView((string) $m->id, (string) $m->name)
        )->all();
    }
}
