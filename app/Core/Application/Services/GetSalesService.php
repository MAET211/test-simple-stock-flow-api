<?php

declare(strict_types=1);

namespace App\Core\Application\Services;

use App\Core\Application\Exceptions\NotFound;
use App\Core\Application\PageRequest;
use App\Core\Application\Ports\In\GetSales;
use App\Core\Application\Ports\Out\SaleRepository;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\SaleItemView;
use App\Core\Application\Views\SaleView;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\SaleId;

final readonly class GetSalesService implements GetSales
{
    public function __construct(
        private SaleRepository $saleRepository
    ) {}

    public function get(SaleId $id): SaleView
    {
        $sale = $this->saleRepository->find($id);
        if ($sale === null) {
            throw new NotFound;
        }

        $items = [];
        foreach ($sale->items() as $item) {
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
            $sale->id()->value(),
            $sale->soldAt(),
            $sale->soldByUsername(),
            $sale->total(),
            $items
        );
    }

    /**
     * @return PagedResult<SaleView>
     */
    public function listByRange(DateRange $range, PageRequest $pageRequest): PagedResult
    {
        return $this->saleRepository->search($range, $pageRequest);
    }
}
