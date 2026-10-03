<?php

declare(strict_types=1);

use App\Core\Domain\ValueObjects\Money;
use App\Core\Infrastructure\Adapters\Out\Persistence\Mappers\MoneyColumn;

test('money column converts boundary values between decimal strings and money without float loss', function (string $decimal, int $cents): void {
    $money = Money::ofCents($cents);

    expect(MoneyColumn::toDecimalString($money))->toBe($decimal);

    $parsed = MoneyColumn::fromDecimalString($decimal);
    expect($parsed->amountCents)->toBe($cents)
        ->and($parsed->currency)->toBe('COP');
})->with([
    ['0.00', 0],
    ['0.01', 1],
    ['0.10', 10],
    ['19.99', 1999],
    ['15000.00', 1500000],
    ['9999999999.99', 999999999999],
]);

test('money column rejects malformed decimal strings', function (string $invalid): void {
    expect(fn () => MoneyColumn::fromDecimalString($invalid))->toThrow(InvalidArgumentException::class);
})->with([
    '',
    'abc',
    '19.9',
    '19.999',
    '-19.99',
    '19',
    '19,99',
]);
