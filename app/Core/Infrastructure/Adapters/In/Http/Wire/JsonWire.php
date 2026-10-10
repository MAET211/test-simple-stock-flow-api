<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Wire;

use App\Core\Application\Views\AuthResult;
use App\Core\Application\Views\CategoryView;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\ProductView;
use App\Core\Application\Views\SaleItemView;
use App\Core\Application\Views\SalesReport;
use App\Core\Application\Views\SalesReportRow;
use App\Core\Application\Views\SaleView;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Infrastructure\Adapters\In\Http\Errors\ValidationFailedException;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Request;

final class JsonWire
{
    private const string UUID_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private const string ISO_DATE_REGEX = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/';

    public static function isValidUuid(string $val): bool
    {
        return preg_match(self::UUID_REGEX, $val) === 1;
    }

    public static function assertValidUuid(string $val, string $field = 'id', bool $isPath = false): void
    {
        if (! self::isValidUuid($val)) {
            throw new ValidationFailedException([$field => ['Invalid UUID format']], $isPath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseBody(Request $request): array
    {
        $raw = (string) $request->getContent();
        if ($raw === '') {
            return [];
        }

        try {
            /** @var mixed $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                throw new ValidationFailedException(['body' => ['Expected JSON object']]);
            }

            /** @var array<string, mixed> $data */
            return $data;
        } catch (\JsonException) {
            throw new ValidationFailedException(['body' => ['Invalid JSON format']]);
        }
    }

    public static function parseIsoDate(mixed $val, string $field): DateTimeImmutable
    {
        if (! is_string($val) || $val === '') {
            throw new ValidationFailedException([$field => ['Field required']]);
        }

        if (preg_match(self::ISO_DATE_REGEX, $val) !== 1) {
            throw new ValidationFailedException([$field => ['Invalid ISO 8601 date format with explicit offset']]);
        }

        try {
            $date = new DateTimeImmutable($val);

            return $date->setTimezone(new DateTimeZone('UTC'));
        } catch (\Throwable) {
            throw new ValidationFailedException([$field => ['Invalid date value']]);
        }
    }

    public static function parseDateRange(Request $request): DateRange
    {
        $errors = [];
        $fromRaw = $request->query('from');
        $toRaw = $request->query('to');

        if ($fromRaw === null || $fromRaw === '') {
            $errors['from'] = ['Field required'];
        }
        if ($toRaw === null || $toRaw === '') {
            $errors['to'] = ['Field required'];
        }

        if ($errors !== []) {
            throw new ValidationFailedException($errors);
        }

        /** @var string $fromRaw */
        /** @var string $toRaw */
        $from = self::parseIsoDate($fromRaw, 'from');
        $to = self::parseIsoDate($toRaw, 'to');

        return new DateRange($from, $to);
    }

    public static function formatMoney(Money $money): float|int
    {
        $cents = $money->amountCents;
        if ($cents % 100 === 0) {
            return (int) ($cents / 100);
        }

        return (float) number_format($cents / 100, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    public static function authResultToWire(AuthResult $result): array
    {
        return [
            'accessToken' => $result->accessToken,
            'expiresAt' => $result->expiresAt->setTimezone(new DateTimeZone('+00:00'))->format('Y-m-d\TH:i:s\+00:00'),
            'username' => $result->username,
            'role' => $result->role,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function productToWire(ProductView $view): array
    {
        return [
            'id' => $view->id,
            'name' => $view->name,
            'price' => self::formatMoney($view->price),
            'currency' => 'COP',
            'stock' => $view->stock,
            'categoryId' => $view->categoryId,
            'categoryName' => $view->categoryName,
            'imageUrl' => $view->imageUrl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function saleItemToWire(SaleItemView $view): array
    {
        return [
            'productId' => $view->productId,
            'productName' => $view->productName,
            'quantity' => $view->quantity,
            'unitPrice' => self::formatMoney($view->unitPrice),
            'subtotal' => self::formatMoney($view->subtotal),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function saleToWire(SaleView $view): array
    {
        return [
            'id' => $view->id,
            'soldAt' => $view->soldAt->setTimezone(new DateTimeZone('+00:00'))->format('Y-m-d\TH:i:s\+00:00'),
            'soldBy' => $view->soldBy,
            'total' => self::formatMoney($view->total),
            'currency' => 'COP',
            'items' => array_map([self::class, 'saleItemToWire'], $view->items),
        ];
    }

    /**
     * @template T
     *
     * @param  PagedResult<T>  $pagedResult
     * @param  callable(T): array<string, mixed>  $itemMapper
     * @return array<string, mixed>
     */
    public static function pagedResultToWire(PagedResult $pagedResult, callable $itemMapper): array
    {
        return [
            'items' => array_map($itemMapper, $pagedResult->items),
            'page' => $pagedResult->page,
            'size' => $pagedResult->size,
            'total' => $pagedResult->total,
            'totalPages' => $pagedResult->totalPages,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function categoryToWire(CategoryView $view): array
    {
        return [
            'id' => $view->id,
            'name' => $view->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function salesReportRowToWire(SalesReportRow $row): array
    {
        return [
            'productId' => $row->productId,
            'productName' => $row->productName,
            'categoryName' => $row->categoryName,
            'unitsSold' => $row->unitsSold,
            'revenue' => self::formatMoney($row->revenue),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function salesReportToWire(SalesReport $report): array
    {
        return [
            'from' => $report->from->setTimezone(new DateTimeZone('+00:00'))->format('Y-m-d\TH:i:s\+00:00'),
            'to' => $report->to->setTimezone(new DateTimeZone('+00:00'))->format('Y-m-d\TH:i:s\+00:00'),
            'salesCount' => $report->salesCount,
            'grandTotal' => self::formatMoney($report->grandTotal),
            'currency' => 'COP',
            'rows' => array_map([self::class, 'salesReportRowToWire'], $report->rows),
        ];
    }
}
