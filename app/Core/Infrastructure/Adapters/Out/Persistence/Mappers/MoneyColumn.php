<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Mappers;

use App\Core\Domain\ValueObjects\Money;
use InvalidArgumentException;

final class MoneyColumn
{
    public static function toDecimalString(Money $money): string
    {
        $cents = $money->amountCents;
        $integerPart = intdiv($cents, 100);
        $fractionPart = $cents % 100;

        return sprintf('%d.%02d', $integerPart, $fractionPart);
    }

    public static function fromDecimalString(string $decimal): Money
    {
        if (preg_match('/^\d+\.\d{2}$/', $decimal) !== 1) {
            throw new InvalidArgumentException("Invalid decimal string format for money: '{$decimal}'");
        }

        [$intStr, $fracStr] = explode('.', $decimal);
        $cents = ((int) $intStr * 100) + (int) $fracStr;

        return Money::ofCents($cents);
    }
}
