<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObjects;

/**
 * UUID in text form. Generated with the standard library only (random_bytes).
 */
abstract class EntityId
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private const NIL = '00000000-0000-0000-0000-000000000000';

    final protected function __construct(private readonly string $value) {}

    final public static function fromString(string $value): static
    {
        $normalized = strtolower($value);

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw new \InvalidArgumentException("Invalid identifier: '{$value}'.");
        }

        return new static($normalized);
    }

    final public static function generate(): static
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);

        return new static(sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        ));
    }

    final public static function nil(): static
    {
        return new static(self::NIL);
    }

    final public function value(): string
    {
        return $this->value;
    }

    final public function isNil(): bool
    {
        return $this->value === self::NIL;
    }

    final public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    final public function __toString(): string
    {
        return $this->value;
    }
}
