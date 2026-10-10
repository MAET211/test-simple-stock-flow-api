<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Controllers;

use App\Core\Application\Ports\In\GetSalesReport;
use App\Core\Infrastructure\Adapters\In\Http\Wire\JsonWire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SalesReportController
{
    public function index(Request $request, GetSalesReport $reportPort): JsonResponse
    {
        $range = JsonWire::parseDateRange($request);
        $report = $reportPort->report($range);

        return new JsonResponse(JsonWire::salesReportToWire($report), 200);
    }
}
