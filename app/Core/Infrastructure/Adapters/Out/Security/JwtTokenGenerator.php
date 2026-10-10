<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Security;

use App\Core\Application\Ports\Out\Clock;
use App\Core\Application\Ports\Out\TokenGenerator;
use App\Core\Application\Views\AuthResult;
use App\Core\Domain\ValueObjects\UserId;
use DateTimeImmutable;
use DateTimeZone;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

final class JwtTokenGenerator implements TokenGenerator
{
    private string $signingKey;

    private int $lifetimeMinutes;

    public function __construct(
        private Clock $clock,
        ?string $signingKey = null,
        ?int $lifetimeMinutes = null
    ) {
        $keyVal = $signingKey ?? getenv('JWT_SIGNING_KEY');
        $this->signingKey = is_string($keyVal) && $keyVal !== '' ? $keyVal : 'default_jwt_secret_key_32_characters_long';

        $lifeVal = $lifetimeMinutes ?? getenv('JWT_LIFETIME_MINUTES');
        $this->lifetimeMinutes = is_numeric($lifeVal) ? (int) $lifeVal : 60;
    }

    public function generate(UserId $userId, string $username, string $role): AuthResult
    {
        $now = $this->clock->now()->getTimestamp();
        $exp = $now + ($this->lifetimeMinutes * 60);

        $payload = [
            'sub' => $userId->value(),
            'unique_name' => $username,
            'role' => $role,
            'jti' => Str::uuid()->toString(),
            'iat' => $now,
            'exp' => $exp,
        ];

        $token = JWT::encode($payload, $this->signingKey, 'HS256');

        $expiresAt = (new DateTimeImmutable('@'.$exp))
            ->setTimezone(new DateTimeZone('UTC'));

        return new AuthResult(
            $token,
            $expiresAt,
            $username,
            $role
        );
    }
}
