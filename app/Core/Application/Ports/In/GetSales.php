<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\In;

use App\Core\Application\PageRequest;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\SaleView;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\SaleId;

interface GetSales
{
    public function get(SaleId $id): SaleView;

    /**
     * @return PagedResult<SaleView>
     */
    public function listByRange(DateRange $range, PageRequest $pageRequest): PagedResult;
}
