<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\In;

use App\Core\Application\Views\SalesReport;
use App\Core\Domain\ValueObjects\DateRange;

interface GetSalesReport
{
    public function report(DateRange $range): SalesReport;
}
