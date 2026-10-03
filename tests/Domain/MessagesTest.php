<?php

declare(strict_types=1);

use App\Core\Domain\Exceptions\BusinessRuleViolation;
use App\Core\Domain\Exceptions\CategoryRequired;
use App\Core\Domain\Exceptions\DuplicateProductInSale;
use App\Core\Domain\Exceptions\InsufficientStock;
use App\Core\Domain\Exceptions\InvalidDateRange;
use App\Core\Domain\Exceptions\InvalidQuantity;
use App\Core\Domain\Exceptions\InvalidRole;
use App\Core\Domain\Exceptions\NegativeInitialStock;
use App\Core\Domain\Exceptions\PriceMustBePositive;
use App\Core\Domain\Exceptions\ProductNameRequired;
use App\Core\Domain\Exceptions\SaleWithoutItems;

it('every domain exception carries the literal text of the contract', function (BusinessRuleViolation $e, string $text): void {
    expect($e->getMessage())->toBe($text);
})->with([
    [new ProductNameRequired, 'El nombre del producto es obligatorio.'],
    [new PriceMustBePositive, 'El precio debe ser mayor a cero.'],
    [new NegativeInitialStock, 'El stock inicial no puede ser negativo.'],
    [new CategoryRequired, 'La categoría es obligatoria.'],
    [new InvalidQuantity, 'La cantidad debe ser mayor a cero.'],
    [new InsufficientStock('Martillo', 3, 5), "Stock insuficiente para 'Martillo': disponible 3, solicitado 5."],
    [new SaleWithoutItems, 'La venta debe tener al menos un ítem.'],
    [new DuplicateProductInSale, 'La venta tiene productos repetidos.'],
    [new InvalidDateRange, 'La fecha final no puede ser anterior a la inicial.'],
    [new InvalidRole('x'), "Rol no válido: 'x'."],
]);
