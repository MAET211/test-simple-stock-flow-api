<?php

declare(strict_types=1);

use App\Core\Domain\Category;
use App\Core\Domain\Product;
use App\Core\Domain\Sale;
use App\Core\Domain\User;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;
use App\Core\Domain\ValueObjects\UserId;
use App\Core\Infrastructure\Adapters\Out\Persistence\Mappers\ProductMapper;
use App\Core\Infrastructure\Adapters\Out\Persistence\Mappers\SaleMapper;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\ProductModel;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\UserModel;
use Illuminate\Support\Facades\DB;

test('materializ persists product price 15000.00 as exact decimal and reconstitutes to 1_500_000 cents', function (): void {
    $catId = CategoryId::of('11111111-1111-4111-8111-111111111111');
    $productId = ProductId::generate();
    $product = Product::create($productId, 'Martillo Demo', Money::ofCents(1_500_000), 10, $catId);

    $model = ProductMapper::toModel($product);
    $model->version = 1;
    $model->save();

    $raw = DB::table('product')->where('id', $productId->value)->first();
    expect($raw)->not->toBeNull()
        ->and($raw->price)->toBe('15000.00');

    $reconstituted = ProductMapper::toDomain(ProductModel::findOrFail($productId->value));
    expect($reconstituted->price()->amountCents)->toBe(1_500_000)
        ->and($reconstituted->price()->currency)->toBe('COP');
});

test('materializ boundary prices preserve exact cents without float errors', function (int $cents, string $expectedDecimal): void {
    $catId = CategoryId::of('11111111-1111-4111-8111-111111111111');
    $productId = ProductId::generate();
    $product = Product::create($productId, 'Item '.$cents, Money::ofCents($cents), 5, $catId);

    $model = ProductMapper::toModel($product);
    $model->version = 1;
    $model->save();

    $raw = DB::table('product')->where('id', $productId->value)->value('price');
    expect($raw)->toBe($expectedDecimal);

    $reconstituted = ProductMapper::toDomain(ProductModel::findOrFail($productId->value));
    expect($reconstituted->price()->amountCents)->toBe($cents);
})->with([
    [1, '0.01'],
    [1999, '19.99'],
    [999999999999, '9999999999.99'],
]);

test('materializ sale items reconstruct quantity using Quantity value object', function (): void {
    $catId = CategoryId::of('11111111-1111-4111-8111-111111111111');
    $category = Category::reconstitute($catId, 'General');

    $userId = UserId::generate();
    $user = User::register($userId, 'cajero1', 'hash_pass_123', 'seller');
    UserModel::create([
        'id' => $user->id()->value,
        'username' => $user->username(),
        'password_hash' => $user->passwordHash(),
        'role' => $user->role(),
    ]);

    $prodId = ProductId::generate();
    $product = Product::create($prodId, 'Clavos 2 pulg', Money::ofCents(50000), 100, $catId);
    $prodModel = ProductMapper::toModel($product);
    $prodModel->version = 1;
    $prodModel->save();

    $soldAt = new DateTimeImmutable('2026-10-03T10:00:00+00:00');
    $sale = Sale::open($soldAt, $userId, 'cajero1');
    $sale->addItem($product, $category, Quantity::of(4));

    SaleMapper::save($sale);

    $loaded = SaleMapper::findById($sale->id());
    expect($loaded)->not->toBeNull();
    $item = $loaded->items()[0];
    expect($item->quantity())->toBeInstanceOf(Quantity::class)
        ->and($item->quantity()->value)->toBe(4);
});

test('materializ sale items preserve frozen product and category copies and unit price', function (): void {
    $catId = CategoryId::of('22222222-2222-4222-8222-222222222222');
    $category = Category::reconstitute($catId, 'Herramientas');

    $userId = UserId::generate();
    UserModel::create([
        'id' => $userId->value,
        'username' => 'vendedor2',
        'password_hash' => 'hash_pass_456',
        'role' => 'seller',
    ]);

    $prodId = ProductId::generate();
    $product = Product::create($prodId, 'Destornillador Phillips', Money::ofCents(25000), 50, $catId);
    $prodModel = ProductMapper::toModel($product);
    $prodModel->version = 1;
    $prodModel->save();

    $sale = Sale::open(new DateTimeImmutable('2026-10-03T11:00:00+00:00'), $userId, 'vendedor2');
    $sale->addItem($product, $category, Quantity::of(2));
    SaleMapper::save($sale);

    // Modify active product in DB
    $prodModel->name = 'Destornillador Modificado';
    $prodModel->price = '999.00';
    $prodModel->save();

    // Reconstituted sale line must retain the frozen copies
    $loaded = SaleMapper::findById($sale->id());
    $item = $loaded->items()[0];
    expect($item->productName())->toBe('Destornillador Phillips')
        ->and($item->categoryName())->toBe('Herramientas')
        ->and($item->unitPrice()->amountCents)->toBe(25000);
});

test('materializ sold_at with offset and microseconds is stored in UTC and reconstituted in UTC', function (): void {
    $userId = UserId::generate();
    UserModel::create([
        'id' => $userId->value,
        'username' => 'cajero_tz',
        'password_hash' => 'hash_123',
        'role' => 'seller',
    ]);

    $catId = CategoryId::of('11111111-1111-4111-8111-111111111111');
    $category = Category::reconstitute($catId, 'General');
    $prodId = ProductId::generate();
    $product = Product::create($prodId, 'Tornillos', Money::ofCents(1000), 20, $catId);
    $prodModel = ProductMapper::toModel($product);
    $prodModel->version = 1;
    $prodModel->save();

    $soldAtBogota = new DateTimeImmutable('2026-10-03T12:00:00.123456-05:00');
    $sale = Sale::open($soldAtBogota, $userId, 'cajero_tz');
    $sale->addItem($product, $category, Quantity::of(1));
    SaleMapper::save($sale);

    $raw = DB::table('sale')->where('id', $sale->id()->value)->first();
    expect($raw->sold_at)->toBe('2026-10-03 17:00:00.123456');

    $loaded = SaleMapper::findById($sale->id());
    expect($loaded->soldAt()->format('Y-m-d H:i:s.u'))->toBe('2026-10-03 17:00:00.123456')
        ->and($loaded->soldAt()->getTimezone()->getName())->toBe('+00:00');
});

test('materializ sold_at maintains UTC even when date_default_timezone_set is altered', function (): void {
    $userId = UserId::generate();
    UserModel::create([
        'id' => $userId->value,
        'username' => 'cajero_tz2',
        'password_hash' => 'hash_123',
        'role' => 'seller',
    ]);

    $catId = CategoryId::of('11111111-1111-4111-8111-111111111111');
    $category = Category::reconstitute($catId, 'General');
    $prodId = ProductId::generate();
    $product = Product::create($prodId, 'Tuercas', Money::ofCents(2000), 20, $catId);
    $prodModel = ProductMapper::toModel($product);
    $prodModel->version = 1;
    $prodModel->save();

    $sale = Sale::open(new DateTimeImmutable('2026-10-03T15:30:00.000000+00:00'), $userId, 'cajero_tz2');
    $sale->addItem($product, $category, Quantity::of(1));
    SaleMapper::save($sale);

    $previousTz = date_default_timezone_get();
    try {
        date_default_timezone_set('America/Bogota');
        $loaded = SaleMapper::findById($sale->id());
        expect($loaded->soldAt()->getTimezone()->getName())->toBe('+00:00')
            ->and($loaded->soldAt()->format('Y-m-d H:i:s'))->toBe('2026-10-03 15:30:00');
    } finally {
        date_default_timezone_set($previousTz);
    }
});

test('materializ version and deleted_at exist only on persistence model and not on Product domain entity', function (): void {
    $ref = new ReflectionClass(Product::class);

    expect($ref->hasProperty('version'))->toBeFalse()
        ->and($ref->hasProperty('deletedAt'))->toBeFalse()
        ->and($ref->hasProperty('deleted_at'))->toBeFalse()
        ->and($ref->hasMethod('version'))->toBeFalse()
        ->and($ref->hasMethod('deletedAt'))->toBeFalse();

    $modelRef = new ReflectionClass(ProductModel::class);
    $catId = CategoryId::of('11111111-1111-4111-8111-111111111111');
    $product = Product::create(ProductId::generate(), 'Item Check', Money::ofCents(100), 5, $catId);
    $model = ProductMapper::toModel($product);
    $model->version = 4;
    $model->deleted_at = '2026-10-03 12:00:00.000000';
    $model->save();

    $raw = DB::table('product')->where('id', $product->id()->value)->first();
    expect($raw->version)->toBe(4)
        ->and($raw->deleted_at)->toBe('2026-10-03 12:00:00.000000');
});

test('materializ sale items preserve insertion order and SaleItemId', function (): void {
    $catId = CategoryId::of('11111111-1111-4111-8111-111111111111');
    $category = Category::reconstitute($catId, 'General');

    $userId = UserId::generate();
    UserModel::create([
        'id' => $userId->value,
        'username' => 'cajero_order',
        'password_hash' => 'hash_order',
        'role' => 'seller',
    ]);

    $p1 = Product::create(ProductId::generate(), 'Item A', Money::ofCents(100), 10, $catId);
    $m1 = ProductMapper::toModel($p1);
    $m1->version = 1;
    $m1->save();

    $p2 = Product::create(ProductId::generate(), 'Item B', Money::ofCents(200), 10, $catId);
    $m2 = ProductMapper::toModel($p2);
    $m2->version = 1;
    $m2->save();

    $sale = Sale::open(new DateTimeImmutable('2026-10-03T16:00:00+00:00'), $userId, 'cajero_order');
    $sale->addItem($p1, $category, Quantity::of(1));
    $sale->addItem($p2, $category, Quantity::of(2));
    SaleMapper::save($sale);

    $originalItemIds = array_map(fn ($i) => $i->id()->value, $sale->items());

    $loaded = SaleMapper::findById($sale->id());
    $loadedItemIds = array_map(fn ($i) => $i->id()->value, $loaded->items());

    expect($loadedItemIds)->toBe($originalItemIds);
});
