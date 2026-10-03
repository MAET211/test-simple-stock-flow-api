<?php

declare(strict_types=1);

use App\Core\Domain\Exceptions\CurrencyMismatch;
use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use Tests\Support\Make;

it('rn_09 rejects creating a product with a price in another currency', function (): void {
    expect(fn () => Product::create(
        ProductId::generate(),
        'Martillo',
        Money::ofCents(1_500_000, 'USD'),
        1,
        Make::category()->id(),
    ))->toThrow(CurrencyMismatch::class);
});

it('rn_09 rejects changing the price to another currency', function (): void {
    $product = Make::product();

    expect(fn () => $product->changePrice(Money::ofCents(1_500_000, 'USD')))
        ->toThrow(CurrencyMismatch::class, 'La moneda USD no es la del sistema (COP).');
});

it('rn_09 refuses to add amounts of two currencies', function (): void {
    expect(fn () => Money::ofCents(100)->add(Money::ofCents(100, 'USD')))
        ->toThrow(CurrencyMismatch::class);
});
