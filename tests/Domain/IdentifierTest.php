<?php

declare(strict_types=1);

use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\ProductId;

it('generates a version 4 UUID', function (): void {
    expect(ProductId::generate()->value())
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

it('rejects a malformed identifier', function (): void {
    expect(fn () => ProductId::fromString('not-a-uuid'))->toThrow(InvalidArgumentException::class);
});

it('recognizes the nil identifier', function (): void {
    expect(CategoryId::nil()->isNil())->toBeTrue()
        ->and(CategoryId::generate()->isNil())->toBeFalse();
});

it('compares by value and by type', function (): void {
    $value = '11111111-1111-4111-8111-111111111111';

    expect(ProductId::fromString($value)->equals(ProductId::fromString($value)))->toBeTrue()
        ->and(ProductId::fromString($value)->equals(CategoryId::fromString($value)))->toBeFalse();
});
