<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Repositories;

use App\Core\Application\PageRequest;
use App\Core\Application\Ports\Out\SaleRepository;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\SaleItemView;
use App\Core\Application\Views\SaleView;
use App\Core\Domain\Sale;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\SaleId;
use App\Core\Infrastructure\Adapters\Out\Persistence\Mappers\MoneyColumn;
use App\Core\Infrastructure\Adapters\Out\Persistence\Mappers\SaleMapper;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\SaleItemModel;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\SaleModel;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;

final class EloquentSaleRepository implements SaleRepository
{
    public function find(SaleId $id): ?Sale
    {
        return SaleMapper::findById($id);
    }

    /**
     * @return PagedResult<SaleView>
     */
    public function search(DateRange $range, PageRequest $pageRequest): PagedResult
    {
        $fromStr = $range->from->setTimezone(new DateTimeZone('+00:00'))->format('Y-m-d H:i:s.u');
        $toStr = $range->to->setTimezone(new DateTimeZone('+00:00'))->format('Y-m-d H:i:s.u');

        $query = SaleModel::query()
            ->with('items')
            ->where('sold_at', '>=', $fromStr)
            ->where('sold_at', '<', $toStr);

        $total = (int) $query->count();

        /** @var Collection<int, SaleModel> $models */
        $models = $query->orderBy('sold_at', 'desc')
            ->orderBy('id', 'desc')
            ->skip($pageRequest->offset())
            ->take($pageRequest->size)
            ->get();

        $views = $models->map(
            static function (SaleModel $m): SaleView {
                $soldAt = new DateTimeImmutable((string) $m->sold_at, new DateTimeZone('+00:00'));

                $items = [];
                $saleTotalCents = 0;

                /** @var Collection<int, SaleItemModel> $itemModels */
                $itemModels = $m->getRelationValue('items');

                foreach ($itemModels as $itemModel) {
                    $unitPrice = MoneyColumn::fromDecimalString((string) $itemModel->unit_price);
                    $subtotalCents = $unitPrice->amountCents * (int) $itemModel->quantity;
                    $saleTotalCents += $subtotalCents;

                    $items[] = new SaleItemView(
                        (string) $itemModel->product_id,
                        (string) $itemModel->product_name,
                        (string) $itemModel->category_name,
                        (int) $itemModel->quantity,
                        $unitPrice,
                        Money::ofCents($subtotalCents)
                    );
                }

                return new SaleView(
                    (string) $m->id,
                    $soldAt,
                    (string) $m->sold_by_username,
                    Money::ofCents($saleTotalCents),
                    $items,
                    'COP'
                );
            }
        )->all();

        return new PagedResult($views, $pageRequest->page, $pageRequest->size, $total);
    }

    public function add(Sale $sale): void
    {
        SaleMapper::save($sale);
    }
}
