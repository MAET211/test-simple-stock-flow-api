<?php

declare(strict_types=1);

use App\Core\Domain\Exceptions\DuplicateProductInSale;
use App\Core\Domain\Exceptions\InsufficientStock;
use App\Core\Domain\Exceptions\SaleWithoutItems;
use App\Core\Domain\Sale;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\Quantity;
use App\Core\Domain\ValueObjects\UserId;
use Tests\Support\Make;

function openSale(): Sale
{
    return Sale::open(new DateTimeImmutable('2026-10-03T10:00:00+00:00'), UserId::generate(), 'ana');
}

it('rn_04 rejects confirming a sale without items', function (): void {
    expect(fn () => openSale()->ensureConfirmable())
        ->toThrow(SaleWithoutItems::class, 'La venta debe tener al menos un ítem.');
});

it('rn_04 accepts confirming a sale with one item', function (): void {
    $category = Make::category();
    $sale = openSale();
    $sale->addItem(Make::product('Martillo', 1_500_000, 10, $category), $category, Quantity::of(1));

    $sale->ensureConfirmable();

    expect($sale->items())->toHaveCount(1);
});

it('rn_05 rejects the same product twice and does not take stock for the duplicate', function (): void {
    $category = Make::category();
    $product = Make::product('Martillo', 1_500_000, 10, $category);
    $sale = openSale();
    $sale->addItem($product, $category, Quantity::of(2));

    expect(fn () => $sale->addItem($product, $category, Quantity::of(3)))
        ->toThrow(DuplicateProductInSale::class, 'La venta tiene productos repetidos.');
    expect($product->stock())->toBe(8);
});

it('adding an item withdraws the stock as one operation', function (): void {
    $category = Make::category();
    $product = Make::product('Martillo', 1_500_000, 10, $category);
    $sale = openSale();

    $sale->addItem($product, $category, Quantity::of(4));

    expect($product->stock())->toBe(6);
});

it('insufficient stock adds no line and leaves the stock intact', function (): void {
    $category = Make::category();
    $product = Make::product('Martillo', 1_500_000, 2, $category);
    $sale = openSale();

    expect(fn () => $sale->addItem($product, $category, Quantity::of(5)))
        ->toThrow(InsufficientStock::class);
    expect($sale->items())->toBeEmpty()
        ->and($product->stock())->toBe(2);
});

it('rn_06 freezes name, price and category name when the item is added', function (): void {
    $category = Make::category('Herramientas');
    $product = Make::product('Martillo', 1_500_000, 10, $category);
    $sale = openSale();
    $sale->addItem($product, $category, Quantity::of(2));

    $product->rename('Martillo grande');
    $product->changePrice(Money::ofCents(9_900_000));
    $category->rename('Otra cosa');

    $item = $sale->items()[0];
    expect($item->productName())->toBe('Martillo')
        ->and($item->unitPrice()->amountCents)->toBe(1_500_000)
        ->and($item->categoryName())->toBe('Herramientas');
});

it('rn_12 total is the sum of the line subtotals', function (): void {
    $category = Make::category();
    $sale = openSale();
    $sale->addItem(Make::product('Martillo', 1_999, 10, $category), $category, Quantity::of(3));
    $sale->addItem(Make::product('Clavos', 50, 100, $category), $category, Quantity::of(10));

    expect($sale->total()->amountCents)->toBe(5997 + 500)
        ->and($sale->total()->currency)->toBe('COP');
});

it('rn_12 an empty sale totals zero', function (): void {
    expect(openSale()->total()->amountCents)->toBe(0);
});

it('rn_07 items() returns a copy: changing it does not change the sale', function (): void {
    $category = Make::category();
    $sale = openSale();
    $sale->addItem(Make::product('Martillo', 1_500_000, 10, $category), $category, Quantity::of(1));

    $copy = $sale->items();
    array_pop($copy);

    expect($sale->items())->toHaveCount(1);
});

it('stores the sale instant in UTC', function (): void {
    $sale = Sale::open(new DateTimeImmutable('2026-10-03T10:00:00-05:00'), UserId::generate(), 'ana');

    expect($sale->soldAt()->format('c'))->toBe('2026-10-03T15:00:00+00:00');
});

it('requires the username of who makes the sale', function (): void {
    expect(fn () => Sale::open(new DateTimeImmutable('now'), UserId::generate(), '  '))
        ->toThrow(InvalidArgumentException::class);
});
