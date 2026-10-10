<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class ForceJsonMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $request->headers->set('Accept', 'application/json');

        /** @var SymfonyResponse $response */
        $response = $next($request);

        return $response;
    }
}
