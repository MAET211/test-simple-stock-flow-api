<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Mappers;

use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\ProductModel;

final class ProductMapper
{
    public static function toDomain(ProductModel $model): Product
    {
        return Product::reconstitute(
            ProductId::fromString($model->id),
            $model->name,
            MoneyColumn::fromDecimalString($model->price),
            $model->stock,
            CategoryId::fromString($model->category_id),
            $model->image_key
        );
    }

    public static function toModel(Product $product): ProductModel
    {
        /** @var ProductModel $model */
        $model = ProductModel::find($product->id()->value()) ?? new ProductModel;
        $model->id = $product->id()->value();
        $model->name = $product->name();
        $model->price = MoneyColumn::toDecimalString($product->price());
        $model->stock = $product->stock();
        $model->category_id = $product->categoryId()->value();
        $model->image_key = $product->imageKey();

        return $model;
    }
}
