<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class RequireRoleMiddleware
{
    public function handle(Request $request, Closure $next, string $requiredRole): SymfonyResponse
    {
        $user = $request->attributes->get('user');

        if (! is_array($user)) {
            return new Response('', 401, [
                'WWW-Authenticate' => 'Bearer',
                'Content-Length' => '0',
            ]);
        }

        if (($user['role'] ?? null) !== $requiredRole) {
            return new Response('', 403, [
                'Content-Length' => '0',
            ]);
        }

        /** @var SymfonyResponse $response */
        $response = $next($request);

        return $response;
    }
}
