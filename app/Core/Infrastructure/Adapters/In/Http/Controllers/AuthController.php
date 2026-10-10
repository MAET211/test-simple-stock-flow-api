<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Controllers;

use App\Core\Application\Ports\In\Authenticate;
use App\Core\Infrastructure\Adapters\In\Http\Errors\ValidationFailedException;
use App\Core\Infrastructure\Adapters\In\Http\Wire\JsonWire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function login(Request $request, Authenticate $authPort): JsonResponse
    {
        $body = JsonWire::parseBody($request);

        $errors = [];
        if (! array_key_exists('username', $body) || $body['username'] === null) {
            $errors['username'] = ['Field required'];
        }
        if (! array_key_exists('password', $body) || $body['password'] === null) {
            $errors['password'] = ['Field required'];
        }

        if ($errors !== []) {
            throw new ValidationFailedException($errors);
        }

        /** @var string $username */
        $username = $body['username'];
        /** @var string $password */
        $password = $body['password'];

        $result = $authPort->login((string) $username, (string) $password);

        return new JsonResponse(JsonWire::authResultToWire($result), 200);
    }

    public function register(Request $request, Authenticate $authPort): JsonResponse
    {
        $body = JsonWire::parseBody($request);

        $errors = [];
        if (! array_key_exists('username', $body) || $body['username'] === null) {
            $errors['username'] = ['Field required'];
        }
        if (! array_key_exists('password', $body) || $body['password'] === null) {
            $errors['password'] = ['Field required'];
        }
        if (! array_key_exists('role', $body) || $body['role'] === null) {
            $errors['role'] = ['Field required'];
        }

        if ($errors !== []) {
            throw new ValidationFailedException($errors);
        }

        /** @var string $username */
        $username = $body['username'];
        /** @var string $password */
        $password = $body['password'];
        /** @var string $role */
        $role = $body['role'];

        $userId = $authPort->register((string) $username, (string) $password, (string) $role);

        // 201 Created WITHOUT Location header per D-C6 contract
        return new JsonResponse(['id' => $userId->value()], 201);
    }
}
