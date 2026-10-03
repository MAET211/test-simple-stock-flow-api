<?php

declare(strict_types=1);

namespace App\Core\Domain;

use App\Core\Domain\Exceptions\CategoryRequired;
use App\Core\Domain\Exceptions\CurrencyMismatch;
use App\Core\Domain\Exceptions\InsufficientStock;
use App\Core\Domain\Exceptions\NegativeInitialStock;
use App\Core\Domain\Exceptions\PriceMustBePositive;
use App\Core\Domain\Exceptions\ProductNameRequired;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;

/**
 * Catalog aggregate root. It has no "version" and no "deleted_at": both exist only in the
 * persistence model (D-03, D-04).
 */
final class Product
{
    private function __construct(
        private readonly ProductId $id,
        private string $name,
        private Money $price,
        private int $stock,
        private CategoryId $categoryId,
        private ?string $imageKey,
    ) {}

    public static function create(
        ProductId $id,
        string $name,
        Money $price,
        int $stock,
        CategoryId $categoryId,
    ): self {
        $normalizedName = self::normalizeName($name);
        self::assertValidPrice($price);

        if ($stock < 0) {
            throw new NegativeInitialStock;
        }

        self::assertCategory($categoryId);

        return new self($id, $normalizedName, $price, $stock, $categoryId, null);
    }

    /**
     * Rebuilds a stored product. Used by persistence mappers: the row already passed the engine checks.
     */
    public static function reconstitute(
        ProductId $id,
        string $name,
        Money $price,
        int $stock,
        CategoryId $categoryId,
        ?string $imageKey,
    ): self {
        return new self($id, $name, $price, $stock, $categoryId, $imageKey);
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function stock(): int
    {
        return $this->stock;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function imageKey(): ?string
    {
        return $this->imageKey;
    }

    public function rename(string $name): void
    {
        $this->name = self::normalizeName($name);
    }

    public function changePrice(Money $price): void
    {
        self::assertValidPrice($price);
        $this->price = $price;
    }

    public function setCategory(CategoryId $categoryId): void
    {
        self::assertCategory($categoryId);
        $this->categoryId = $categoryId;
    }

    public function withdraw(Quantity $quantity): void
    {
        if ($quantity->value > $this->stock) {
            throw new InsufficientStock($this->name, $this->stock, $quantity->value);
        }

        $this->stock -= $quantity->value;
    }

    public function restock(Quantity $quantity): void
    {
        $this->stock += $quantity->value;
    }

    public function attachImage(?string $imageKey): void
    {
        $this->imageKey = ($imageKey === null || trim($imageKey) === '') ? null : $imageKey;
    }

    private static function normalizeName(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new ProductNameRequired;
        }

        return $trimmed;
    }

    private static function assertValidPrice(Money $price): void
    {
        if (! $price->isSystemCurrency()) {
            throw CurrencyMismatch::notSystemCurrency($price->currency, Money::DEFAULT_CURRENCY);
        }

        if (! $price->isPositive()) {
            throw new PriceMustBePositive;
        }
    }

    private static function assertCategory(CategoryId $categoryId): void
    {
        if ($categoryId->isNil()) {
            throw new CategoryRequired;
        }
    }
}
