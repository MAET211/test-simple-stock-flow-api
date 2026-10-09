<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Exceptions\ConcurrencyConflict;
use App\Core\Application\PageRequest;
use App\Core\Application\Ports\Out\ProductRepository;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\ProductView;
use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\ProductId;

final class FakeProductRepository implements ProductRepository
{
    /** @var array<string, Product> */
    public array $products = [];

    /** @var array<string, bool> */
    public array $discontinued = [];

    /** @var array<string, string> */
    public array $categoryNames = [];

    public int $saveCalls = 0;

    public ?int $failOnSaveAttempt = null;

    private int $currentAttempt = 0;

    public function find(ProductId $id): ?Product
    {
        return $this->products[$id->value()] ?? null;
    }

    /**
     * @return PagedResult<ProductView>
     */
    public function search(?string $search, ?CategoryId $categoryId, PageRequest $pageRequest): PagedResult
    {
        $filtered = array_filter(
            $this->products,
            function (Product $p) use ($search, $categoryId): bool {
                if (isset($this->discontinued[$p->id()->value()])) {
                    return false;
                }

                if ($categoryId !== null && ! $p->categoryId()->equals($categoryId)) {
                    return false;
                }

                if ($search !== null && $search !== '') {
                    $searchLower = mb_strtolower($search, 'UTF-8');
                    $nameLower = mb_strtolower($p->name(), 'UTF-8');
                    if (! str_contains($nameLower, $searchLower)) {
                        return false;
                    }
                }

                return true;
            }
        );

        $list = array_values($filtered);
        usort($list, static fn (Product $a, Product $b): int => strcmp($a->name(), $b->name()));

        $total = count($list);
        $offset = $pageRequest->offset();
        $itemsSlice = array_slice($list, $offset, $pageRequest->size);

        $views = array_map(
            fn (Product $p): ProductView => new ProductView(
                $p->id()->value(),
                $p->name(),
                $p->price(),
                $p->stock(),
                $p->categoryId()->value(),
                $this->categoryNames[$p->categoryId()->value()] ?? '',
                $p->imageKey() !== null ? "/media/{$p->imageKey()}" : null
            ),
            $itemsSlice
        );

        return new PagedResult($views, $pageRequest->page, $pageRequest->size, $total);
    }

    /**
     * @param  array<ProductId>  $ids
     * @return array<string, Product>
     */
    public function findActiveByIds(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $key = $id->value();
            if (isset($this->products[$key]) && ! isset($this->discontinued[$key])) {
                $result[$key] = $this->products[$key];
            }
        }

        return $result;
    }

    public function add(Product $product): void
    {
        $this->products[$product->id()->value()] = $product;
    }

    public function save(Product $product): void
    {
        $this->saveCalls++;
        $this->currentAttempt++;

        if ($this->failOnSaveAttempt !== null && $this->currentAttempt <= $this->failOnSaveAttempt) {
            throw new ConcurrencyConflict;
        }

        $this->products[$product->id()->value()] = $product;
    }

    public function markDiscontinued(ProductId $id): void
    {
        $this->discontinued[$id->value()] = true;
    }
}
