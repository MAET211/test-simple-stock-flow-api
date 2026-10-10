<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence;

use App\Core\Application\Ports\Out\SalesReportQuery;
use App\Core\Application\Views\SalesReport;
use App\Core\Application\Views\SalesReportRow;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Infrastructure\Adapters\Out\Persistence\Mappers\MoneyColumn;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use stdClass;

final class SalesReportSqlQuery implements SalesReportQuery
{
    public function between(DateRange $range): SalesReport
    {
        $fromUtc = $range->from->setTimezone(new DateTimeZone('+00:00'));
        $toUtc = $range->to->setTimezone(new DateTimeZone('+00:00'));

        $fromStr = $fromUtc->format('Y-m-d H:i:s.u');
        $toStr = $toUtc->format('Y-m-d H:i:s.u');

        $countRow = DB::selectOne(
            'SELECT COUNT(DISTINCT s.id) as sales_count FROM sale s WHERE s.sold_at >= ? AND s.sold_at < ?',
            [$fromStr, $toStr]
        );

        $salesCount = ($countRow instanceof stdClass && isset($countRow->sales_count) && is_numeric($countRow->sales_count)) ? (int) $countRow->sales_count : 0;

        $sql = '
            SELECT t.product_id,
                   t.latest_name       AS product_name,
                   t.category_name,
                   SUM(t.quantity)                 AS units_sold,
                   SUM(t.quantity * t.unit_price)  AS revenue
            FROM (
              SELECT i.product_id, i.category_name, i.quantity, i.unit_price,
                     FIRST_VALUE(i.product_name) OVER (
                       PARTITION BY i.product_id
                       ORDER BY s.sold_at DESC, i.id DESC
                     ) AS latest_name
              FROM sale s
              JOIN sale_item i ON i.sale_id = s.id
              WHERE s.sold_at >= ? AND s.sold_at < ?
            ) t
            GROUP BY t.product_id, t.latest_name, t.category_name
            ORDER BY revenue DESC
        ';

        /** @var array<stdClass> $rows */
        $rows = DB::select($sql, [$fromStr, $toStr]);

        $reportRows = [];
        $grandTotalCents = 0;

        foreach ($rows as $row) {
            $revenueVal = isset($row->revenue) && is_numeric($row->revenue) ? (float) $row->revenue : 0.0;
            $revenueDecimal = number_format($revenueVal, 2, '.', '');
            $revenueMoney = MoneyColumn::fromDecimalString($revenueDecimal);
            $grandTotalCents += $revenueMoney->amountCents;

            $productId = isset($row->product_id) && is_scalar($row->product_id) ? (string) $row->product_id : '';
            $productName = isset($row->product_name) && is_scalar($row->product_name) ? (string) $row->product_name : '';
            $categoryName = isset($row->category_name) && is_scalar($row->category_name) ? (string) $row->category_name : '';
            $unitsSold = isset($row->units_sold) && is_numeric($row->units_sold) ? (int) $row->units_sold : 0;

            $reportRows[] = new SalesReportRow(
                $productId,
                $productName,
                $categoryName,
                $unitsSold,
                $revenueMoney
            );
        }

        return new SalesReport(
            $fromUtc,
            $toUtc,
            $salesCount,
            Money::ofCents($grandTotalCents),
            $reportRows,
            'COP'
        );
    }
}
