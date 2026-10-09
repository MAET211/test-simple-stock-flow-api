<?php

declare(strict_types=1);

namespace App\Core\Application\Services;

use App\Core\Application\Ports\In\GetSalesReport;
use App\Core\Application\Ports\Out\SalesReportQuery;
use App\Core\Application\Views\SalesReport;
use App\Core\Domain\ValueObjects\DateRange;

final readonly class SalesReportService implements GetSalesReport
{
    public function __construct(
        private SalesReportQuery $salesReportQuery
    ) {}

    public function report(DateRange $range): SalesReport
    {
        return $this->salesReportQuery->between($range);
    }
}
