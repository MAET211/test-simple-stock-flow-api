<?php

declare(strict_types=1);

use App\Core\Domain\Exceptions\InvalidQuantity;
use App\Core\Domain\ValueObjects\Quantity;

it('rn_03 rejects a zero quantity', function (): void {
    expect(fn () => Quantity::of(0))->toThrow(InvalidQuantity::class, 'La cantidad debe ser mayor a cero.');
});

it('rn_03 rejects a negative quantity', function (): void {
    expect(fn () => Quantity::of(-4))->toThrow(InvalidQuantity::class);
});

it('rn_03 keeps the value when it is 1', function (): void {
    expect(Quantity::of(1)->value)->toBe(1);
});
