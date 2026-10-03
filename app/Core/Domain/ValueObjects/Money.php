<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObjects;

use App\Core\Domain\Exceptions\CurrencyMismatch;

/**
 * Amount in integer cents plus a currency code. Zero is valid (a subtotal can be zero);
 * the "price > 0" guard lives in Product (RN-02). Operating two currencies fails (RN-09).
 */
final class Money
{
    public const DEFAULT_CURRENCY = 'COP';

    private function __construct(
        public readonly int $amountCents,
        public readonly string $currency,
    ) {}

    public static function ofCents(int $amountCents, string $currency = self::DEFAULT_CURRENCY): self
    {
        if ($amountCents < 0) {
            throw new \InvalidArgumentException('Money cannot be negative.');
        }

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new \InvalidArgumentException("Invalid currency code: '{$currency}'.");
        }

        return new self($amountCents, $currency);
    }

    public static function zero(string $currency = self::DEFAULT_CURRENCY): self
    {
        return self::ofCents(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::ofCents($this->amountCents + $other->amountCents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        if ($factor < 0) {
            throw new \InvalidArgumentException('Factor cannot be negative.');
        }

        return self::ofCents($this->amountCents * $factor, $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->amountCents > 0;
    }

    public function isSystemCurrency(): bool
    {
        return $this->currency === self::DEFAULT_CURRENCY;
    }

    public function equals(self $other): bool
    {
        return $this->amountCents === $other->amountCents && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw CurrencyMismatch::between($this->currency, $other->currency);
        }
    }
}
