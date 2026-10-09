<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

use App\Core\Application\Views\SalesReport;
use App\Core\Domain\ValueObjects\DateRange;

interface SalesReportQuery
{
    public function between(DateRange $range): SalesReport;
}
