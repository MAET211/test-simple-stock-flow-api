<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\PageRequest;
use App\Core\Application\Ports\Out\SaleRepository;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\SaleItemView;
use App\Core\Application\Views\SaleView;
use App\Core\Domain\Sale;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\SaleId;

final class FakeSaleRepository implements SaleRepository
{
    /** @var array<string, Sale> */
    public array $sales = [];

    public function find(SaleId $id): ?Sale
    {
        return $this->sales[$id->value()] ?? null;
    }

    /**
     * @return PagedResult<SaleView>
     */
    public function search(DateRange $range, PageRequest $pageRequest): PagedResult
    {
        $filtered = array_filter(
            $this->sales,
            static fn (Sale $s): bool => $range->contains($s->soldAt())
        );

        $list = array_values($filtered);
        usort($list, static fn (Sale $a, Sale $b): int => $b->soldAt() <=> $a->soldAt());

        $total = count($list);
        $offset = $pageRequest->offset();
        $slice = array_slice($list, $offset, $pageRequest->size);

        $views = array_map(
            static function (Sale $s): SaleView {
                $items = [];
                foreach ($s->items() as $item) {
                    $items[] = new SaleItemView(
                        $item->productId()->value(),
                        $item->productName(),
                        $item->categoryName(),
                        $item->quantity()->value,
                        $item->unitPrice(),
                        $item->subtotal()
                    );
                }

                return new SaleView(
                    $s->id()->value(),
                    $s->soldAt(),
                    $s->soldByUsername(),
                    $s->total(),
                    $items
                );
            },
            $slice
        );

        return new PagedResult($views, $pageRequest->page, $pageRequest->size, $total);
    }

    public function add(Sale $sale): void
    {
        $this->sales[$sale->id()->value()] = $sale;
    }
}
