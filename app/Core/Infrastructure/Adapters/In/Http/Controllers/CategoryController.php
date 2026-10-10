<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Controllers;

use App\Core\Application\Ports\In\ManageProducts;
use App\Core\Infrastructure\Adapters\In\Http\Wire\JsonWire;
use Illuminate\Http\JsonResponse;

final class CategoryController
{
    public function index(ManageProducts $productPort): JsonResponse
    {
        $categories = $productPort->listCategories();
        $wire = array_map([JsonWire::class, 'categoryToWire'], $categories);

        return new JsonResponse($wire, 200);
    }
}
