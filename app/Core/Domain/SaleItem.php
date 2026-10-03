<?php

declare(strict_types=1);

namespace App\Core\Domain;

use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;
use App\Core\Domain\ValueObjects\SaleItemId;

/**
 * Sale line. Name, price and category name are frozen copies taken when the sale is made
 * (RN-06). Immutable. Only Sale::addItem creates lines; reconstitute() is for persistence mappers.
 */
final class SaleItem
{
    private function __construct(
        private readonly SaleItemId $id,
        private readonly ProductId $productId,
        private readonly string $productName,
        private readonly string $categoryName,
        private readonly Quantity $quantity,
        private readonly Money $unitPrice,
    ) {}

    /**
     * @internal Called by Sale::addItem only.
     */
    public static function freeze(Product $product, Category $category, Quantity $quantity): self
    {
        return new self(
            SaleItemId::generate(),
            $product->id(),
            $product->name(),
            $category->name(),
            $quantity,
            $product->price(),
        );
    }

    public static function reconstitute(
        SaleItemId $id,
        ProductId $productId,
        string $productName,
        string $categoryName,
        Quantity $quantity,
        Money $unitPrice,
    ): self {
        return new self($id, $productId, $productName, $categoryName, $quantity, $unitPrice);
    }

    public function id(): SaleItemId
    {
        return $this->id;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function categoryName(): string
    {
        return $this->categoryName;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity->value);
    }
}
