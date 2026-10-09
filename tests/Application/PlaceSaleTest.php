<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Core\Application\Commands\PlaceSaleCommand;
use App\Core\Application\Commands\PlaceSaleLineCommand;
use App\Core\Application\Exceptions\ConcurrencyConflict;
use App\Core\Application\Exceptions\ReferenceNotFound;
use App\Core\Application\Services\PlaceSaleService;
use App\Core\Domain\Category;
use App\Core\Domain\Exceptions\DuplicateProductInSale;
use App\Core\Domain\Exceptions\InsufficientStock;
use App\Core\Domain\Exceptions\SaleWithoutItems;
use App\Core\Domain\Product;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;
use App\Core\Domain\ValueObjects\UserId;
use Tests\Support\Fakes\FakeCategoryRepository;
use Tests\Support\Fakes\FakeClock;
use Tests\Support\Fakes\FakeProductRepository;
use Tests\Support\Fakes\FakeSaleRepository;
use Tests\Support\Fakes\FakeUnitOfWork;

beforeEach(function (): void {
    $this->productRepo = new FakeProductRepository;
    $this->categoryRepo = new FakeCategoryRepository;
    $this->saleRepo = new FakeSaleRepository;
    $this->unitOfWork = new FakeUnitOfWork;
    $this->clock = new FakeClock;

    $this->service = new PlaceSaleService(
        $this->productRepo,
        $this->categoryRepo,
        $this->saleRepo,
        $this->unitOfWork,
        $this->clock
    );

    $this->catId = CategoryId::generate();
    $this->category = Category::create($this->catId, 'Herramientas');
    $this->categoryRepo->add($this->category);

    $this->prodId = ProductId::generate();
    $this->product = Product::create($this->prodId, 'Taladro', Money::ofCents(5000000), 10, $this->catId);
    $this->productRepo->add($this->product);

    $this->userId = UserId::generate();
    $this->username = 'carlos';
});

test('places a sale withdrawing stock and freezing line details', function (): void {
    $cmd = new PlaceSaleCommand(
        $this->userId,
        $this->username,
        [
            new PlaceSaleLineCommand($this->prodId, Quantity::of(2)),
        ]
    );

    $saleId = $this->service->execute($cmd);

    expect($this->product->stock())->toBe(8);
    expect($this->unitOfWork->commitCount)->toBe(1);

    $savedSale = $this->saleRepo->find($saleId);
    expect($savedSale)->not->toBeNull();
    expect($savedSale->soldByUsername())->toBe('carlos');
    expect($savedSale->total()->amountCents)->toBe(10000000);
    expect($savedSale->items())->toHaveCount(1);

    $item = $savedSale->items()[0];
    expect($item->productName())->toBe('Taladro');
    expect($item->categoryName())->toBe('Herramientas');
    expect($item->unitPrice()->amountCents)->toBe(5000000);
    expect($item->quantity()->value)->toBe(2);
});

test('rejects sale without items with SaleWithoutItems', function (): void {
    $cmd = new PlaceSaleCommand(
        $this->userId,
        $this->username,
        []
    );

    expect(fn () => $this->service->execute($cmd))
        ->toThrow(SaleWithoutItems::class);
});

test('rejects sale with duplicate product with DuplicateProductInSale', function (): void {
    $cmd = new PlaceSaleCommand(
        $this->userId,
        $this->username,
        [
            new PlaceSaleLineCommand($this->prodId, Quantity::of(1)),
            new PlaceSaleLineCommand($this->prodId, Quantity::of(2)),
        ]
    );

    expect(fn () => $this->service->execute($cmd))
        ->toThrow(DuplicateProductInSale::class);

    expect($this->product->stock())->toBe(10);
});

test('rejects sale with non-existent product with ReferenceNotFound', function (): void {
    $missingId = ProductId::generate();
    $cmd = new PlaceSaleCommand(
        $this->userId,
        $this->username,
        [
            new PlaceSaleLineCommand($missingId, Quantity::of(1)),
        ]
    );

    expect(fn () => $this->service->execute($cmd))
        ->toThrow(ReferenceNotFound::class, "El producto {$missingId->value()} no existe.");
});

test('rejects sale with insufficient stock with InsufficientStock', function (): void {
    $cmd = new PlaceSaleCommand(
        $this->userId,
        $this->username,
        [
            new PlaceSaleLineCommand($this->prodId, Quantity::of(99)),
        ]
    );

    expect(fn () => $this->service->execute($cmd))
        ->toThrow(InsufficientStock::class);

    expect($this->product->stock())->toBe(10);
});

test('retries on ConcurrencyConflict up to 3 times and succeeds on retry', function (): void {
    $this->productRepo->failOnSaveAttempt = 1; // Fails on attempt 1, succeeds on attempt 2

    $cmd = new PlaceSaleCommand(
        $this->userId,
        $this->username,
        [
            new PlaceSaleLineCommand($this->prodId, Quantity::of(1)),
        ]
    );

    $saleId = $this->service->execute($cmd);

    expect($this->saleRepo->find($saleId))->not->toBeNull();
    expect($this->unitOfWork->discardCount)->toBe(2);
});

test('exhausts 3 attempts and throws ConcurrencyConflict', function (): void {
    $this->productRepo->failOnSaveAttempt = 3; // Fails on attempts 1, 2, and 3

    $cmd = new PlaceSaleCommand(
        $this->userId,
        $this->username,
        [
            new PlaceSaleLineCommand($this->prodId, Quantity::of(1)),
        ]
    );

    expect(fn () => $this->service->execute($cmd))
        ->toThrow(ConcurrencyConflict::class);

    expect($this->unitOfWork->discardCount)->toBe(3);
});
