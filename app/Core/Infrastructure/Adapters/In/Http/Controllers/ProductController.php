<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Controllers;

use App\Core\Application\Commands\AttachImageCommand;
use App\Core\Application\Commands\SaveProductCommand;
use App\Core\Application\PageRequest;
use App\Core\Application\Ports\In\ManageProducts;
use App\Core\Domain\ValueObjects\CategoryId;
use App\Core\Domain\ValueObjects\Money;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Infrastructure\Adapters\In\Http\Errors\ValidationFailedException;
use App\Core\Infrastructure\Adapters\In\Http\Wire\JsonWire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ProductController
{
    public function index(Request $request, ManageProducts $productPort): JsonResponse
    {
        $errors = [];

        $categoryIdRaw = $request->query('categoryId');
        $categoryId = null;
        if ($categoryIdRaw !== null && $categoryIdRaw !== '') {
            if (! is_string($categoryIdRaw) || ! JsonWire::isValidUuid($categoryIdRaw)) {
                $errors['categoryId'] = ['Invalid UUID format'];
            } else {
                $categoryId = CategoryId::fromString($categoryIdRaw);
            }
        }

        $pageRaw = $request->query('page');
        $page = 1;
        if ($pageRaw !== null && $pageRaw !== '') {
            if (! is_numeric($pageRaw) || (string) (int) $pageRaw !== (string) $pageRaw) {
                $errors['page'] = ['Expected integer'];
            } else {
                $page = (int) $pageRaw;
            }
        }

        $sizeRaw = $request->query('size');
        $size = 20;
        if ($sizeRaw !== null && $sizeRaw !== '') {
            if (! is_numeric($sizeRaw) || (string) (int) $sizeRaw !== (string) $sizeRaw) {
                $errors['size'] = ['Expected integer'];
            } else {
                $size = (int) $sizeRaw;
            }
        }

        if ($errors !== []) {
            throw new ValidationFailedException($errors);
        }

        $search = $request->query('search');
        $searchStr = is_string($search) && $search !== '' ? $search : null;

        $pageRequest = new PageRequest($page, $size);
        $result = $productPort->list($searchStr, $categoryId, $pageRequest);

        $wire = JsonWire::pagedResultToWire($result, [JsonWire::class, 'productToWire']);

        return new JsonResponse($wire, 200);
    }

    public function show(string $id, ManageProducts $productPort): JsonResponse
    {
        if (! JsonWire::isValidUuid($id)) {
            throw new ValidationFailedException(['id' => ['Invalid UUID format']], true);
        }

        $view = $productPort->get(ProductId::fromString($id));

        return new JsonResponse(JsonWire::productToWire($view), 200);
    }

    public function store(Request $request, ManageProducts $productPort): JsonResponse
    {
        $body = JsonWire::parseBody($request);
        $cmd = $this->validateAndBuildSaveCommand($body);

        $id = $productPort->create($cmd);

        return new JsonResponse(
            ['id' => $id->value()],
            201,
            ['Location' => '/api/products/'.$id->value()]
        );
    }

    public function update(string $id, Request $request, ManageProducts $productPort): Response
    {
        if (! JsonWire::isValidUuid($id)) {
            throw new ValidationFailedException(['id' => ['Invalid UUID format']], true);
        }

        $body = JsonWire::parseBody($request);
        $cmd = $this->validateAndBuildSaveCommand($body);

        $productPort->update(ProductId::fromString($id), $cmd);

        return new Response('', 204, ['Content-Length' => '0']);
    }

    public function destroy(string $id, ManageProducts $productPort): Response
    {
        if (! JsonWire::isValidUuid($id)) {
            throw new ValidationFailedException(['id' => ['Invalid UUID format']], true);
        }

        $productPort->discontinue(ProductId::fromString($id));

        return new Response('', 204, ['Content-Length' => '0']);
    }

    public function uploadImage(string $id, Request $request, ManageProducts $productPort): JsonResponse
    {
        if (! JsonWire::isValidUuid($id)) {
            throw new ValidationFailedException(['id' => ['Invalid UUID format']], true);
        }

        if (! $request->hasFile('file')) {
            throw new ValidationFailedException(['file' => ['Field required']]);
        }

        $file = $request->file('file');
        if ($file === null || ! $file->isValid()) {
            throw new ValidationFailedException(['file' => ['Invalid file upload']]);
        }

        $bytes = (string) file_get_contents($file->getRealPath());
        $contentType = (string) ($file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream');

        $command = new AttachImageCommand(
            ProductId::fromString($id),
            $bytes,
            $contentType
        );

        $url = $productPort->attachImage($command);

        return new JsonResponse(['url' => $url], 200);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function validateAndBuildSaveCommand(array $body): SaveProductCommand
    {
        $errors = [];

        if (! array_key_exists('name', $body) || $body['name'] === null) {
            $errors['name'] = ['Field required'];
        } elseif (! is_string($body['name'])) {
            $errors['name'] = ['Expected string'];
        }

        if (! array_key_exists('price', $body) || $body['price'] === null) {
            $errors['price'] = ['Field required'];
        } elseif (is_bool($body['price']) || (! is_int($body['price']) && ! is_float($body['price']))) {
            $errors['price'] = ['Expected number'];
        }

        if (! array_key_exists('stock', $body) || $body['stock'] === null) {
            $errors['stock'] = ['Field required'];
        } elseif (is_bool($body['stock']) || ! is_int($body['stock'])) {
            $errors['stock'] = ['Expected integer'];
        }

        if (! array_key_exists('categoryId', $body) || $body['categoryId'] === null) {
            $errors['categoryId'] = ['Field required'];
        } elseif (! is_string($body['categoryId']) || ! JsonWire::isValidUuid($body['categoryId'])) {
            $errors['categoryId'] = ['Invalid UUID format'];
        }

        if ($errors !== []) {
            throw new ValidationFailedException($errors);
        }

        /** @var string $name */
        $name = $body['name'];
        /** @var int|float $price */
        $price = $body['price'];
        /** @var int $stock */
        $stock = $body['stock'];
        /** @var string $categoryId */
        $categoryId = $body['categoryId'];

        $priceCents = (int) round(((float) $price) * 100);
        $priceMoney = $priceCents >= 0 ? Money::ofCents($priceCents) : Money::ofCents(0);

        return new SaveProductCommand(
            $name,
            $priceMoney,
            $stock,
            CategoryId::fromString($categoryId)
        );
    }
}
