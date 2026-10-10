<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Repositories;

use App\Core\Application\Exceptions\ConcurrencyConflict;
use App\Core\Application\PageRequest;
use App\Core\Application\Ports\Out\ProductRepository;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\ProductView;
use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Infrastructure\Adapters\Out\Persistence\Mappers\MoneyColumn;
use App\Core\Infrastructure\Adapters\Out\Persistence\Mappers\ProductMapper;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\CategoryModel;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\ProductModel;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;

final class EloquentProductRepository implements ProductRepository
{
    public function find(ProductId $id): ?Product
    {
        /** @var ProductModel|null $model */
        $model = ProductModel::query()->find($id->value());
        if ($model === null) {
            return null;
        }

        return ProductMapper::toDomain($model);
    }

    /**
     * @return PagedResult<ProductView>
     */
    public function search(?string $search, ?CategoryId $categoryId, PageRequest $pageRequest): PagedResult
    {
        $query = ProductModel::query()
            ->with('category')
            ->whereNull('deleted_at');

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId->value());
        }

        if ($search !== null && trim($search) !== '') {
            $escaped = addcslashes($search, '%_');
            $query->where('name', 'LIKE', "%{$escaped}%");
        }

        $total = (int) $query->count();

        /** @var Collection<int, ProductModel> $models */
        $models = $query->orderBy('name', 'asc')
            ->skip($pageRequest->offset())
            ->take($pageRequest->size)
            ->get();

        $views = $models->map(
            static function (ProductModel $m): ProductView {
                /** @var CategoryModel|null $cat */
                $cat = $m->getRelationValue('category');
                $categoryName = $cat !== null ? (string) $cat->name : '';
                $imageUrl = $m->image_key !== null ? "/media/{$m->image_key}" : null;

                return new ProductView(
                    (string) $m->id,
                    (string) $m->name,
                    MoneyColumn::fromDecimalString((string) $m->price),
                    (int) $m->stock,
                    (string) $m->category_id,
                    $categoryName,
                    $imageUrl
                );
            }
        )->all();

        return new PagedResult($views, $pageRequest->page, $pageRequest->size, $total);
    }

    /**
     * @param  array<ProductId>  $ids
     * @return array<string, Product>
     */
    public function findActiveByIds(array $ids): array
    {
        $stringIds = array_map(static fn (ProductId $id): string => $id->value(), $ids);
        /** @var Collection<int, ProductModel> $models */
        $models = ProductModel::query()
            ->whereNull('deleted_at')
            ->whereIn('id', $stringIds)
            ->get();

        $result = [];
        foreach ($models as $model) {
            $product = ProductMapper::toDomain($model);
            $result[$product->id()->value()] = $product;
        }

        return $result;
    }

    public function add(Product $product): void
    {
        ProductModel::query()->create([
            'id' => $product->id()->value(),
            'name' => $product->name(),
            'price' => MoneyColumn::toDecimalString($product->price()),
            'stock' => $product->stock(),
            'category_id' => $product->categoryId()->value(),
            'image_key' => $product->imageKey(),
            'version' => 1,
            'deleted_at' => null,
        ]);
    }

    public function save(Product $product, bool $discontinue = false): void
    {
        /** @var ProductModel|null $currentModel */
        $currentModel = ProductModel::query()->find($product->id()->value());
        if ($currentModel === null) {
            throw new ConcurrencyConflict;
        }

        $nowUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        $deletedAt = $discontinue ? ($currentModel->deleted_at ?? $nowUtc) : $currentModel->deleted_at;

        $affected = ProductModel::query()
            ->where('id', $product->id()->value())
            ->where('version', $currentModel->version)
            ->update([
                'name' => $product->name(),
                'price' => MoneyColumn::toDecimalString($product->price()),
                'stock' => $product->stock(),
                'category_id' => $product->categoryId()->value(),
                'image_key' => $product->imageKey(),
                'version' => $currentModel->version + 1,
                'deleted_at' => $deletedAt,
            ]);

        if ($affected === 0) {
            throw new ConcurrencyConflict;
        }
    }
}
