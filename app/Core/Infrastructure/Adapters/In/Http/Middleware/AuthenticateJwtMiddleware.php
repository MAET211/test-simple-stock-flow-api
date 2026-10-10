<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

final class AuthenticateJwtMiddleware
{
    private string $signingKey;

    public function __construct(?string $signingKey = null)
    {
        $keyVal = $signingKey ?? getenv('JWT_SIGNING_KEY');
        $this->signingKey = is_string($keyVal) && $keyVal !== '' ? $keyVal : 'default_jwt_secret_key_32_characters_long';
    }

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $authHeader = $request->header('Authorization');

        if ($authHeader === null || ! str_starts_with($authHeader, 'Bearer ')) {
            return new Response('', 401, [
                'WWW-Authenticate' => 'Bearer',
                'Content-Length' => '0',
            ]);
        }

        $token = substr($authHeader, 7);

        try {
            JWT::$leeway = 30;
            $decoded = JWT::decode($token, new Key($this->signingKey, 'HS256'));

            if (! isset($decoded->sub, $decoded->unique_name, $decoded->role)
                || ! is_scalar($decoded->sub)
                || ! is_scalar($decoded->unique_name)
                || ! is_scalar($decoded->role)
            ) {
                return new Response('', 401, [
                    'WWW-Authenticate' => 'Bearer error="invalid_token"',
                    'Content-Length' => '0',
                ]);
            }

            $user = [
                'id' => (string) $decoded->sub,
                'username' => (string) $decoded->unique_name,
                'role' => (string) $decoded->role,
            ];

            $request->attributes->set('user', $user);
        } catch (Throwable) {
            return new Response('', 401, [
                'WWW-Authenticate' => 'Bearer error="invalid_token"',
                'Content-Length' => '0',
            ]);
        }

        /** @var SymfonyResponse $response */
        $response = $next($request);

        return $response;
    }
}
