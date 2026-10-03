<?php

declare(strict_types=1);

use App\Core\Domain\ValueObjects\Money;

it('uses COP as the default currency', function (): void {
    expect(Money::ofCents(100)->currency)->toBe('COP')
        ->and(Money::DEFAULT_CURRENCY)->toBe('COP');
});

it('accepts zero because a subtotal can be zero', function (): void {
    expect(Money::ofCents(0)->amountCents)->toBe(0)
        ->and(Money::ofCents(0)->isPositive())->toBeFalse();
});

it('rejects a negative amount', function (): void {
    expect(fn () => Money::ofCents(-1))->toThrow(InvalidArgumentException::class);
});

it('rejects a malformed currency code', function (): void {
    expect(fn () => Money::ofCents(1, 'cop'))->toThrow(InvalidArgumentException::class);
});

it('adds amounts of the same currency without losing cents', function (): void {
    $sum = Money::ofCents(1999)->add(Money::ofCents(1));

    expect($sum->amountCents)->toBe(2000);
});

it('multiplies by an integer factor', function (): void {
    expect(Money::ofCents(1999)->multiply(3)->amountCents)->toBe(5997);
});
