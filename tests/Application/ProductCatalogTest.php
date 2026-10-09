<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Core\Application\Commands\AttachImageCommand;
use App\Core\Application\Commands\SaveProductCommand;
use App\Core\Application\Exceptions\ImageTooLarge;
use App\Core\Application\Exceptions\ImageTypeNotAllowed;
use App\Core\Application\Exceptions\NotFound;
use App\Core\Application\Exceptions\ReferenceNotFound;
use App\Core\Application\PageRequest;
use App\Core\Application\Services\ProductCatalogService;
use App\Core\Domain\Category;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use Tests\Support\Fakes\FakeCategoryRepository;
use Tests\Support\Fakes\FakeFileStorage;
use Tests\Support\Fakes\FakeProductRepository;
use Tests\Support\Fakes\FakeUnitOfWork;

beforeEach(function (): void {
    $this->productRepo = new FakeProductRepository;
    $this->categoryRepo = new FakeCategoryRepository;
    $this->fileStorage = new FakeFileStorage;
    $this->unitOfWork = new FakeUnitOfWork;

    $this->service = new ProductCatalogService(
        $this->productRepo,
        $this->categoryRepo,
        $this->fileStorage,
        $this->unitOfWork
    );

    $this->catId = CategoryId::generate();
    $this->category = Category::create($this->catId, 'Pinturas');
    $this->categoryRepo->add($this->category);
    $this->productRepo->categoryNames[$this->catId->value()] = 'Pinturas';
});

test('creates a product with valid category', function (): void {
    $cmd = new SaveProductCommand('Esmalte Sintético', Money::ofCents(2500000), 15, $this->catId);
    $id = $this->service->create($cmd);

    $product = $this->productRepo->find($id);
    expect($product)->not->toBeNull();
    expect($product->name())->toBe('Esmalte Sintético');
    expect($product->stock())->toBe(15);
    expect($this->unitOfWork->commitCount)->toBe(1);
});

test('rejects creating a product with non-existent category', function (): void {
    $missingCatId = CategoryId::generate();
    $cmd = new SaveProductCommand('Esmalte', Money::ofCents(2500000), 15, $missingCatId);

    expect(fn () => $this->service->create($cmd))
        ->toThrow(ReferenceNotFound::class, "La categoría {$missingCatId->value()} no existe.");
});

test('updates an existing product and adjusts stock', function (): void {
    $cmd = new SaveProductCommand('Esmalte', Money::ofCents(2000000), 10, $this->catId);
    $id = $this->service->create($cmd);

    $updateCmd = new SaveProductCommand('Esmalte Brillante', Money::ofCents(3000000), 12, $this->catId);
    $this->service->update($id, $updateCmd);

    $updated = $this->productRepo->find($id);
    expect($updated->name())->toBe('Esmalte Brillante');
    expect($updated->price()->amountCents)->toBe(3000000);
    expect($updated->stock())->toBe(12);
});

test('updating non-existent product throws NotFound', function (): void {
    $missingId = ProductId::generate();
    $cmd = new SaveProductCommand('Esmalte', Money::ofCents(2000000), 10, $this->catId);

    expect(fn () => $this->service->update($missingId, $cmd))
        ->toThrow(NotFound::class);
});

test('discontinues a product and cleans up image', function (): void {
    $cmd = new SaveProductCommand('Esmalte', Money::ofCents(2000000), 10, $this->catId);
    $id = $this->service->create($cmd);

    $imgCmd = new AttachImageCommand($id, 'fake_png_bytes', 'image/png');
    $this->service->attachImage($imgCmd);

    $product = $this->productRepo->find($id);
    $key = $product->imageKey();
    expect($key)->not->toBeNull();
    expect($this->fileStorage->has($key))->toBeTrue();

    $this->service->discontinue($id);

    expect($product->imageKey())->toBeNull();
    expect($this->fileStorage->has($key))->toBeFalse();
});

test('attaches image to product successfully', function (): void {
    $cmd = new SaveProductCommand('Esmalte', Money::ofCents(2000000), 10, $this->catId);
    $id = $this->service->create($cmd);

    $imgCmd = new AttachImageCommand($id, 'binary_jpeg_data', 'image/jpeg');
    $url = $this->service->attachImage($imgCmd);

    expect($url)->toStartWith('/media/');
    $product = $this->productRepo->find($id);
    expect($product->imageKey())->not->toBeNull();
});

test('rejects image with disallowed content type', function (): void {
    $cmd = new SaveProductCommand('Esmalte', Money::ofCents(2000000), 10, $this->catId);
    $id = $this->service->create($cmd);

    $imgCmd = new AttachImageCommand($id, 'gif_data', 'image/gif');

    expect(fn () => $this->service->attachImage($imgCmd))
        ->toThrow(ImageTypeNotAllowed::class, 'Tipo de archivo no permitido: image/gif.');
});

test('rejects image exceeding 5MB', function (): void {
    $cmd = new SaveProductCommand('Esmalte', Money::ofCents(2000000), 10, $this->catId);
    $id = $this->service->create($cmd);

    $largeBytes = str_repeat('A', (5 * 1024 * 1024) + 1);
    $imgCmd = new AttachImageCommand($id, $largeBytes, 'image/jpeg');

    expect(fn () => $this->service->attachImage($imgCmd))
        ->toThrow(ImageTooLarge::class, 'La imagen supera el máximo de 5 MB.');
});

test('lists products with pagination and filters', function (): void {
    $cmd1 = new SaveProductCommand('Pintura Blanca', Money::ofCents(1000000), 5, $this->catId);
    $cmd2 = new SaveProductCommand('Pintura Negra', Money::ofCents(1200000), 5, $this->catId);
    $this->service->create($cmd1);
    $this->service->create($cmd2);

    $result = $this->service->list('blanca', null, new PageRequest(1, 10));
    expect($result->total)->toBe(1);
    expect($result->items[0]->name)->toBe('Pintura Blanca');
});

test('lists categories in alphabetical order', function (): void {
    $cat2 = Category::create(CategoryId::generate(), 'Electricidad');
    $this->categoryRepo->add($cat2);

    $categories = $this->service->listCategories();
    expect($categories)->toHaveCount(2);
    expect($categories[0]->name)->toBe('Electricidad');
    expect($categories[1]->name)->toBe('Pinturas');
});
