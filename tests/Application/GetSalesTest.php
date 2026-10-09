<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Core\Application\Exceptions\NotFound;
use App\Core\Application\PageRequest;
use App\Core\Application\Services\GetSalesService;
use App\Core\Domain\Category;
use App\Core\Domain\Product;
use App\Core\Domain\Sale;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\DateRange;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;
use App\Core\Domain\ValueObjects\SaleId;
use App\Core\Domain\ValueObjects\UserId;
use DateTimeImmutable;
use DateTimeZone;
use Tests\Support\Fakes\FakeSaleRepository;

beforeEach(function (): void {
    $this->saleRepo = new FakeSaleRepository;
    $this->service = new GetSalesService($this->saleRepo);

    $catId = CategoryId::generate();
    $this->category = Category::create($catId, 'General');

    $prodId = ProductId::generate();
    $this->product = Product::create($prodId, 'Clavos', Money::ofCents(50000), 100, $catId);

    $this->userId = UserId::generate();
    $this->soldAt = new DateTimeImmutable('2026-10-03 14:30:00', new DateTimeZone('UTC'));

    $this->sale = Sale::open($this->soldAt, $this->userId, 'mario');
    $this->sale->addItem($this->product, $this->category, Quantity::of(5));
    $this->saleRepo->add($this->sale);
});

test('get existing sale returns SaleView with calculated totals and items', function (): void {
    $view = $this->service->get($this->sale->id());

    expect($view->id)->toBe($this->sale->id()->value());
    expect($view->soldBy)->toBe('mario');
    expect($view->total->amountCents)->toBe(250000);
    expect($view->currency)->toBe('COP');
    expect($view->items)->toHaveCount(1);
    expect($view->items[0]->productName)->toBe('Clavos');
    expect($view->items[0]->quantity)->toBe(5);
    expect($view->items[0]->unitPrice->amountCents)->toBe(50000);
    expect($view->items[0]->subtotal->amountCents)->toBe(250000);
});

test('get non-existent sale throws NotFound', function (): void {
    $missingId = SaleId::generate();

    expect(fn () => $this->service->get($missingId))
        ->toThrow(NotFound::class);
});

test('listByRange returns paged sales in date range', function (): void {
    $from = new DateTimeImmutable('2026-10-01 00:00:00', new DateTimeZone('UTC'));
    $to = new DateTimeImmutable('2026-10-05 00:00:00', new DateTimeZone('UTC'));
    $range = new DateRange($from, $to);

    $result = $this->service->listByRange($range, new PageRequest(1, 10));

    expect($result->total)->toBe(1);
    expect($result->items[0]->id)->toBe($this->sale->id()->value());
});
