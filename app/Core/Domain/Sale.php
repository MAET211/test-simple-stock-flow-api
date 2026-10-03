<?php

declare(strict_types=1);

namespace App\Core\Domain;

use App\Core\Domain\Exceptions\DuplicateProductInSale;
use App\Core\Domain\Exceptions\SaleWithoutItems;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\Quantity;
use App\Core\Domain\ValueObjects\SaleId;
use App\Core\Domain\ValueObjects\UserId;

/**
 * Sales aggregate root. Immutable once registered (RN-07): it exposes no operation to remove or
 * replace lines, and items() returns a copy. The total is computed, never stored (RN-12).
 */
final class Sale
{
    /** @var list<SaleItem> */
    private array $items;

    /**
     * @param  list<SaleItem>  $items
     */
    private function __construct(
        private readonly SaleId $id,
        private readonly \DateTimeImmutable $soldAt,
        private readonly UserId $soldByUserId,
        private readonly string $soldByUsername,
        array $items,
    ) {
        $this->items = $items;
    }

    public static function open(\DateTimeImmutable $soldAt, UserId $soldByUserId, string $soldByUsername): self
    {
        if (trim($soldByUsername) === '') {
            throw new \InvalidArgumentException('A sale needs the username of who makes it.');
        }

        return new self(
            SaleId::generate(),
            $soldAt->setTimezone(new \DateTimeZone('UTC')),
            $soldByUserId,
            $soldByUsername,
            [],
        );
    }

    /**
     * @param  list<SaleItem>  $items
     */
    public static function reconstitute(
        SaleId $id,
        \DateTimeImmutable $soldAt,
        UserId $soldByUserId,
        string $soldByUsername,
        array $items,
    ): self {
        return new self($id, $soldAt, $soldByUserId, $soldByUsername, $items);
    }

    public function addItem(Product $product, Category $category, Quantity $quantity): void
    {
        if (! $category->id()->equals($product->categoryId())) {
            throw new \InvalidArgumentException('The category does not belong to the product.');
        }

        foreach ($this->items as $item) {
            if ($item->productId()->equals($product->id())) {
                throw new DuplicateProductInSale;
            }
        }

        // Withdraw first: if stock is short, no line is added.
        $product->withdraw($quantity);
        $this->items[] = SaleItem::freeze($product, $category, $quantity);
    }

    public function ensureConfirmable(): void
    {
        if ($this->items === []) {
            throw new SaleWithoutItems;
        }
    }

    public function total(): Money
    {
        $total = Money::zero();

        foreach ($this->items as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }

    public function id(): SaleId
    {
        return $this->id;
    }

    public function soldAt(): \DateTimeImmutable
    {
        return $this->soldAt;
    }

    public function soldByUserId(): UserId
    {
        return $this->soldByUserId;
    }

    public function soldByUsername(): string
    {
        return $this->soldByUsername;
    }

    /**
     * @return list<SaleItem>
     */
    public function items(): array
    {
        return $this->items;
    }
}
