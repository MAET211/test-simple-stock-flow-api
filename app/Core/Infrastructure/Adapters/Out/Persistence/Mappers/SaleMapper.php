<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Mappers;

use App\Core\Domain\Sale;
use App\Core\Domain\SaleItem;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;
use App\Core\Domain\ValueObjects\SaleId;
use App\Core\Domain\ValueObjects\SaleItemId;
use App\Core\Domain\ValueObjects\UserId;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\SaleItemModel;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\SaleModel;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

final class SaleMapper
{
    public static function save(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $soldAtUtc = $sale->soldAt()->setTimezone(new DateTimeZone('+00:00'));

            SaleModel::updateOrCreate(
                ['id' => $sale->id()->value()],
                [
                    'sold_at' => $soldAtUtc->format('Y-m-d H:i:s.u'),
                    'sold_by_username' => $sale->soldByUsername(),
                    'sold_by_user_id' => $sale->soldByUserId()->value(),
                ]
            );

            foreach ($sale->items() as $item) {
                SaleItemModel::updateOrCreate(
                    ['id' => $item->id()->value()],
                    [
                        'sale_id' => $sale->id()->value(),
                        'product_id' => $item->productId()->value(),
                        'product_name' => $item->productName(),
                        'category_name' => $item->categoryName(),
                        'quantity' => $item->quantity()->value,
                        'unit_price' => MoneyColumn::toDecimalString($item->unitPrice()),
                    ]
                );
            }
        });
    }

    public static function findById(SaleId $id): ?Sale
    {
        /** @var SaleModel|null $model */
        $model = SaleModel::with('items')->find($id->value());
        if ($model === null) {
            return null;
        }

        $items = [];
        foreach ($model->items as $itemModel) {
            $items[] = SaleItem::reconstitute(
                SaleItemId::fromString($itemModel->id),
                ProductId::fromString($itemModel->product_id),
                $itemModel->product_name,
                $itemModel->category_name,
                Quantity::of($itemModel->quantity),
                MoneyColumn::fromDecimalString($itemModel->unit_price)
            );
        }

        $soldAt = new DateTimeImmutable($model->sold_at, new DateTimeZone('+00:00'));

        return Sale::reconstitute(
            SaleId::fromString($model->id),
            $soldAt,
            UserId::fromString($model->sold_by_user_id),
            $model->sold_by_username,
            $items
        );
    }
}
