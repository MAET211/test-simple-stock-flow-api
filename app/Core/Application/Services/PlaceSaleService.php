<?php

declare(strict_types=1);

namespace App\Core\Application\Services;

use App\Core\Application\Commands\PlaceSaleCommand;
use App\Core\Application\Exceptions\ConcurrencyConflict;
use App\Core\Application\Exceptions\ReferenceNotFound;
use App\Core\Application\Ports\In\PlaceSale;
use App\Core\Application\Ports\Out\CategoryRepository;
use App\Core\Application\Ports\Out\Clock;
use App\Core\Application\Ports\Out\ProductRepository;
use App\Core\Application\Ports\Out\SaleRepository;
use App\Core\Application\Ports\Out\UnitOfWork;
use App\Core\Domain\Exceptions\DuplicateProductInSale;
use App\Core\Domain\Exceptions\SaleWithoutItems;
use App\Core\Domain\Sale;
use App\Core\Domain\ValueObjects\SaleId;

final readonly class PlaceSaleService implements PlaceSale
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private SaleRepository $saleRepository,
        private UnitOfWork $unitOfWork,
        private Clock $clock
    ) {}

    public function execute(PlaceSaleCommand $command): SaleId
    {
        if ($command->lines === []) {
            throw new SaleWithoutItems;
        }

        $seenProductIds = [];
        foreach ($command->lines as $line) {
            $key = $line->productId->value();
            if (isset($seenProductIds[$key])) {
                throw new DuplicateProductInSale;
            }
            $seenProductIds[$key] = true;
        }

        $maxAttempts = 3;
        $lastConflict = new ConcurrencyConflict;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->unitOfWork->discardChanges();

                $productIds = [];
                foreach ($command->lines as $line) {
                    $productIds[] = $line->productId;
                }

                $activeProducts = $this->productRepository->findActiveByIds($productIds);

                foreach ($productIds as $productId) {
                    if (! isset($activeProducts[$productId->value()])) {
                        throw ReferenceNotFound::product($productId);
                    }
                }

                $categoryIds = [];
                foreach ($activeProducts as $product) {
                    $categoryIds[] = $product->categoryId();
                }

                $categories = $this->categoryRepository->findByIds($categoryIds);

                foreach ($activeProducts as $product) {
                    if (! isset($categories[$product->categoryId()->value()])) {
                        throw ReferenceNotFound::category($product->categoryId());
                    }
                }

                $sale = Sale::open(
                    $this->clock->now(),
                    $command->soldByUserId,
                    $command->soldByUsername
                );

                foreach ($command->lines as $line) {
                    $product = $activeProducts[$line->productId->value()];
                    $category = $categories[$product->categoryId()->value()];

                    $sale->addItem($product, $category, $line->quantity);
                }

                $sale->ensureConfirmable();

                $this->saleRepository->add($sale);
                foreach ($activeProducts as $product) {
                    $this->productRepository->save($product);
                }

                $this->unitOfWork->commit();

                return $sale->id();
            } catch (ConcurrencyConflict $e) {
                $lastConflict = $e;
            }
        }

        throw $lastConflict;
    }
}
