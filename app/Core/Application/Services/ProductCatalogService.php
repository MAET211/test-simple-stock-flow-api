<?php

declare(strict_types=1);

namespace App\Core\Application\Services;

use App\Core\Application\Commands\AttachImageCommand;
use App\Core\Application\Commands\SaveProductCommand;
use App\Core\Application\Exceptions\ImageTooLarge;
use App\Core\Application\Exceptions\ImageTypeNotAllowed;
use App\Core\Application\Exceptions\NotFound;
use App\Core\Application\Exceptions\ReferenceNotFound;
use App\Core\Application\PageRequest;
use App\Core\Application\Ports\In\ManageProducts;
use App\Core\Application\Ports\Out\CategoryRepository;
use App\Core\Application\Ports\Out\FileStorage;
use App\Core\Application\Ports\Out\ProductRepository;
use App\Core\Application\Ports\Out\UnitOfWork;
use App\Core\Application\Views\CategoryView;
use App\Core\Application\Views\PagedResult;
use App\Core\Application\Views\ProductView;
use App\Core\Domain\Exceptions\NegativeInitialStock;
use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;
use Throwable;

final readonly class ProductCatalogService implements ManageProducts
{
    private const int MAX_IMAGE_SIZE_BYTES = 5 * 1024 * 1024; // 5 MB

    private const array ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private FileStorage $fileStorage,
        private UnitOfWork $unitOfWork
    ) {}

    public function create(SaveProductCommand $command): ProductId
    {
        $category = $this->categoryRepository->find($command->categoryId);
        if ($category === null) {
            throw ReferenceNotFound::category($command->categoryId);
        }

        $id = ProductId::generate();
        $product = Product::create(
            $id,
            $command->name,
            $command->price,
            $command->initialStock,
            $command->categoryId
        );

        $this->productRepository->add($product);
        $this->unitOfWork->commit();

        return $product->id();
    }

    public function update(ProductId $id, SaveProductCommand $command): void
    {
        $product = $this->productRepository->find($id);
        if ($product === null) {
            throw new NotFound;
        }

        $category = $this->categoryRepository->find($command->categoryId);
        if ($category === null) {
            throw ReferenceNotFound::category($command->categoryId);
        }

        if ($command->initialStock < 0) {
            throw new NegativeInitialStock;
        }

        $product->rename($command->name);
        $product->changePrice($command->price);
        $product->setCategory($command->categoryId);

        if ($command->initialStock > $product->stock()) {
            $product->restock(Quantity::of($command->initialStock - $product->stock()));
        } elseif ($command->initialStock < $product->stock()) {
            $product->withdraw(Quantity::of($product->stock() - $command->initialStock));
        }

        $this->productRepository->save($product);
        $this->unitOfWork->commit();
    }

    public function discontinue(ProductId $id): void
    {
        $product = $this->productRepository->find($id);
        if ($product === null) {
            throw new NotFound;
        }

        $oldKey = $product->imageKey();
        if ($oldKey !== null) {
            $product->attachImage(null);
            $this->productRepository->save($product);
            $this->unitOfWork->commit();
            $this->fileStorage->delete($oldKey);
        } else {
            $this->productRepository->save($product);
            $this->unitOfWork->commit();
        }
    }

    public function get(ProductId $id): ProductView
    {
        $product = $this->productRepository->find($id);
        if ($product === null) {
            throw new NotFound;
        }

        $category = $this->categoryRepository->find($product->categoryId());
        $categoryName = $category !== null ? $category->name() : '';
        $imageUrl = $product->imageKey() !== null ? $this->fileStorage->urlFor($product->imageKey()) : null;

        return new ProductView(
            $product->id()->value(),
            $product->name(),
            $product->price(),
            $product->stock(),
            $product->categoryId()->value(),
            $categoryName,
            $imageUrl
        );
    }

    /**
     * @return PagedResult<ProductView>
     */
    public function list(?string $search, ?CategoryId $categoryId, PageRequest $pageRequest): PagedResult
    {
        return $this->productRepository->search($search, $categoryId, $pageRequest);
    }

    public function attachImage(AttachImageCommand $command): string
    {
        if (! in_array($command->contentType, self::ALLOWED_IMAGE_TYPES, true)) {
            throw new ImageTypeNotAllowed($command->contentType);
        }

        if (strlen($command->bytes) > self::MAX_IMAGE_SIZE_BYTES) {
            throw new ImageTooLarge;
        }

        $product = $this->productRepository->find($command->productId);
        if ($product === null) {
            throw new NotFound;
        }

        $oldKey = $product->imageKey();
        $key = $this->fileStorage->save($command->bytes, $command->contentType);

        try {
            $product->attachImage($key);
            $this->productRepository->save($product);
            $this->unitOfWork->commit();

            if ($oldKey !== null && $oldKey !== $key) {
                $this->fileStorage->delete($oldKey);
            }

            return $this->fileStorage->urlFor($key);
        } catch (Throwable $e) {
            $this->fileStorage->delete($key);
            throw $e;
        }
    }

    /**
     * @return array<CategoryView>
     */
    public function listCategories(): array
    {
        return $this->categoryRepository->listAll();
    }
}
