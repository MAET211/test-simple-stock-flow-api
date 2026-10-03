<?php

declare(strict_types=1);

use App\Core\Domain\Exceptions\CategoryRequired;
use App\Core\Domain\Exceptions\InsufficientStock;
use App\Core\Domain\Exceptions\NegativeInitialStock;
use App\Core\Domain\Exceptions\PriceMustBePositive;
use App\Core\Domain\Exceptions\ProductNameRequired;
use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;
use Tests\Support\Make;

it('rn_01 refuses to withdraw more stock than available and leaves the stock intact', function (): void {
    $product = Make::product('Martillo', 1_500_000, 3);

    expect(fn () => $product->withdraw(Quantity::of(5)))
        ->toThrow(InsufficientStock::class, "Stock insuficiente para 'Martillo': disponible 3, solicitado 5.");
    expect($product->stock())->toBe(3);
});

it('rn_01 allows withdrawing exactly the available stock', function (): void {
    $product = Make::product('Martillo', 1_500_000, 3);

    $product->withdraw(Quantity::of(3));

    expect($product->stock())->toBe(0);
});

it('rn_01 restock adds to the stock', function (): void {
    $product = Make::product('Martillo', 1_500_000, 3);

    $product->restock(Quantity::of(2));

    expect($product->stock())->toBe(5);
});

it('rn_02 rejects a zero price when creating', function (): void {
    expect(fn () => Make::product('Martillo', 0))
        ->toThrow(PriceMustBePositive::class, 'El precio debe ser mayor a cero.');
});

it('rn_02 rejects a zero price when changing it', function (): void {
    $product = Make::product();

    expect(fn () => $product->changePrice(Money::ofCents(0)))->toThrow(PriceMustBePositive::class);
});

it('changes the price to a positive amount', function (): void {
    $product = Make::product();

    $product->changePrice(Money::ofCents(250_000));

    expect($product->price()->amountCents)->toBe(250_000);
});

it('requires a name and trims it', function (): void {
    expect(fn () => Make::product('   '))
        ->toThrow(ProductNameRequired::class, 'El nombre del producto es obligatorio.');
    expect(Make::product('  Taladro  ')->name())->toBe('Taladro');
});

it('rejects a negative initial stock', function (): void {
    expect(fn () => Make::product('Martillo', 1_500_000, -1))
        ->toThrow(NegativeInitialStock::class, 'El stock inicial no puede ser negativo.');
});

it('requires a category and rejects the nil identifier', function (): void {
    expect(fn () => Product::create(
        ProductId::generate(),
        'Martillo',
        Money::ofCents(1_500_000),
        1,
        CategoryId::nil(),
    ))->toThrow(CategoryRequired::class, 'La categoría es obligatoria.');
});

it('normalizes a blank image key to null, never to an empty string', function (): void {
    $product = Make::product();

    $product->attachImage('abc.png');
    expect($product->imageKey())->toBe('abc.png');

    $product->attachImage('   ');
    expect($product->imageKey())->toBeNull();

    $product->attachImage(null);
    expect($product->imageKey())->toBeNull();
});
