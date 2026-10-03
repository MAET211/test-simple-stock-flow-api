<?php

declare(strict_types=1);

use App\Core\Domain\Exceptions\CategoryNameRequired;
use Tests\Support\Make;

it('trims the category name', function (): void {
    expect(Make::category('  Pinturas  ')->name())->toBe('Pinturas');
});

it('rejects a blank category name', function (): void {
    expect(fn () => Make::category('   '))->toThrow(CategoryNameRequired::class);
});

it('rejects renaming to blank', function (): void {
    $category = Make::category();

    expect(fn () => $category->rename(''))->toThrow(CategoryNameRequired::class);
});
