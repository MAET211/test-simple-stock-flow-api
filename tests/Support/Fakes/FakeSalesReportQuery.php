<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Ports\Out\SalesReportQuery;
use App\Core\Application\Views\SalesReport;
use App\Core\Domain\ValueObjects\DateRange;

final class FakeSalesReportQuery implements SalesReportQuery
{
    public ?SalesReport $cannedReport = null;

    public function between(DateRange $range): SalesReport
    {
        if ($this->cannedReport !== null) {
            return $this->cannedReport;
        }

        return SalesReport::empty($range->from, $range->to);
    }
}
