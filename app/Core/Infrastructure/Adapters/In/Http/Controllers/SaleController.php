<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Controllers;

use App\Core\Application\Commands\PlaceSaleCommand;
use App\Core\Application\Commands\PlaceSaleLineCommand;
use App\Core\Application\PageRequest;
use App\Core\Application\Ports\In\GetSales;
use App\Core\Application\Ports\In\PlaceSale;
use App\Core\Domain\ValueObjects\ProductId;
use App\Core\Domain\ValueObjects\Quantity;
use App\Core\Domain\ValueObjects\SaleId;
use App\Core\Domain\ValueObjects\UserId;
use App\Core\Infrastructure\Adapters\In\Http\Errors\ValidationFailedException;
use App\Core\Infrastructure\Adapters\In\Http\Wire\JsonWire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SaleController
{
    public function store(Request $request, PlaceSale $placeSalePort): JsonResponse
    {
        $body = JsonWire::parseBody($request);

        if (! array_key_exists('lines', $body) || $body['lines'] === null) {
            throw new ValidationFailedException(['lines' => ['Field required']]);
        }

        if (! is_array($body['lines'])) {
            throw new ValidationFailedException(['lines' => ['Expected array']]);
        }

        $lines = [];
        $errors = [];

        foreach ($body['lines'] as $index => $line) {
            if (! is_array($line)) {
                $errors["lines.{$index}"] = ['Expected object'];

                continue;
            }

            if (! array_key_exists('productId', $line) || $line['productId'] === null) {
                $errors["lines.{$index}.productId"] = ['Field required'];
            } elseif (! is_string($line['productId']) || ! JsonWire::isValidUuid($line['productId'])) {
                $errors["lines.{$index}.productId"] = ['Invalid UUID format'];
            }

            if (! array_key_exists('quantity', $line) || $line['quantity'] === null) {
                $errors["lines.{$index}.quantity"] = ['Field required'];
            } elseif (is_bool($line['quantity']) || ! is_int($line['quantity'])) {
                $errors["lines.{$index}.quantity"] = ['Expected integer'];
            }

            if ($errors === []) {
                /** @var string $prodIdStr */
                $prodIdStr = $line['productId'];
                /** @var int $qtyInt */
                $qtyInt = $line['quantity'];

                $lines[] = new PlaceSaleLineCommand(
                    ProductId::fromString($prodIdStr),
                    $qtyInt > 0 ? Quantity::of($qtyInt) : Quantity::of(1)
                );
            }
        }

        if ($errors !== []) {
            throw new ValidationFailedException($errors);
        }

        $commandLines = [];
        foreach ($body['lines'] as $line) {
            /** @var array{productId: string, quantity: int} $line */
            $qty = $line['quantity'] > 0 ? Quantity::of($line['quantity']) : Quantity::of(1);
            $commandLines[] = new PlaceSaleLineCommand(
                ProductId::fromString($line['productId']),
                $qty
            );
        }

        $user = $request->attributes->get('user');
        $userIdStr = is_array($user) && isset($user['id']) && is_scalar($user['id']) ? (string) $user['id'] : '';
        $username = is_array($user) && isset($user['username']) && is_scalar($user['username']) ? (string) $user['username'] : '';

        $sellerId = JsonWire::isValidUuid($userIdStr) ? UserId::fromString($userIdStr) : UserId::generate();

        $command = new PlaceSaleCommand(
            $sellerId,
            $username,
            $commandLines
        );

        $saleId = $placeSalePort->execute($command);

        return new JsonResponse(
            ['id' => $saleId->value()],
            201,
            ['Location' => '/api/sales/'.$saleId->value()]
        );
    }

    public function index(Request $request, GetSales $getSalesPort): JsonResponse
    {
        $range = JsonWire::parseDateRange($request);

        $errors = [];
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

        $pageRequest = new PageRequest($page, $size);
        $result = $getSalesPort->listByRange($range, $pageRequest);

        $wire = JsonWire::pagedResultToWire($result, [JsonWire::class, 'saleToWire']);

        return new JsonResponse($wire, 200);
    }

    public function show(string $id, GetSales $getSalesPort): JsonResponse
    {
        if (! JsonWire::isValidUuid($id)) {
            throw new ValidationFailedException(['id' => ['Invalid UUID format']], true);
        }

        $view = $getSalesPort->get(SaleId::fromString($id));

        return new JsonResponse(JsonWire::saleToWire($view), 200);
    }
}
