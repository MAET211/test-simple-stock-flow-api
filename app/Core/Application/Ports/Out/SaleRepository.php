<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

use App\Core\Application\PageRequest;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\SaleView;
use App\Core\Domain\Sale;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\SaleId;

interface SaleRepository
{
    public function find(SaleId $id): ?Sale;

    /**
     * @return PagedResult<SaleView>
     */
    public function search(DateRange $range, PageRequest $pageRequest): PagedResult;

    public function add(Sale $sale): void;
}
