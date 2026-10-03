<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Domain\Category;
use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;

/**
 * Builders for domain tests. Prices are in cents: 1_500_000 cents = COP 15,000.00.
 */
final class Make
{
    public static function category(string $name = 'Herramientas'): Category
    {
        return Category::create(CategoryId::generate(), $name);
    }

    public static function product(
        string $name = 'Martillo',
        int $priceCents = 1_500_000,
        int $stock = 10,
        ?Category $category = null,
    ): Product {
        $category ??= self::category();

        return Product::create(
            ProductId::generate(),
            $name,
            Money::ofCents($priceCents),
            $stock,
            $category->id(),
        );
    }
}
