<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

/**
 * Internal text: no HTTP request can reach it, because the API accepts no currency (D-05).
 */
final class CurrencyMismatch extends BusinessRuleViolation
{
    public static function notSystemCurrency(string $currency, string $systemCurrency): self
    {
        return new self("La moneda {$currency} no es la del sistema ({$systemCurrency}).");
    }

    public static function between(string $left, string $right): self
    {
        return new self("No se pueden operar importes en monedas distintas: {$left} y {$right}.");
    }
}
