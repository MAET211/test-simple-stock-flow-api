<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Core\Application\Services\SalesReportService;
use App\Core\Application\Views\SalesReport;
use App\Core\Application\Views\SalesReportRow;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\Money;
use DateTimeImmutable;
use DateTimeZone;
use Tests\Support\Fakes\FakeSalesReportQuery;

beforeEach(function (): void {
    $this->query = new FakeSalesReportQuery;
    $this->service = new SalesReportService($this->query);

    $this->from = new DateTimeImmutable('2026-10-01 00:00:00', new DateTimeZone('UTC'));
    $this->to = new DateTimeImmutable('2026-11-01 00:00:00', new DateTimeZone('UTC'));
    $this->range = new DateRange($this->from, $this->to);
});

test('report on empty range returns empty SalesReport with currency COP and grandTotal zero', function (): void {
    $report = $this->service->report($this->range);

    expect($report->salesCount)->toBe(0);
    expect($report->grandTotal->amountCents)->toBe(0);
    expect($report->currency)->toBe('COP');
    expect($report->rows)->toBe([]);
    expect($report->from)->toEqual($this->from);
    expect($report->to)->toEqual($this->to);
});

test('report returns populated rows and grandTotal matching rows sum', function (): void {
    $row1 = new SalesReportRow('prod-1', 'Martillo', 'Herramientas', 3, Money::ofCents(6000000));
    $row2 = new SalesReportRow('prod-2', 'Clavos', 'General', 10, Money::ofCents(4000000));

    $this->query->cannedReport = new SalesReport(
        $this->from,
        $this->to,
        5,
        Money::ofCents(10000000),
        [$row1, $row2]
    );

    $report = $this->service->report($this->range);

    expect($report->salesCount)->toBe(5);
    expect($report->grandTotal->amountCents)->toBe(10000000);
    expect($report->rows)->toHaveCount(2);
    expect($report->rows[0]->productName)->toBe('Martillo');
    expect($report->rows[0]->revenue->amountCents)->toBe(6000000);
});
