<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Core\Domain\ValueObjects\UserId;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use Tests\Support\DatabaseSandbox;

function generateTestToken(string $role = 'admin', string $username = 'admin_user', ?string $userId = null): string
{
    $userId = $userId ?? UserId::generate()->value();
    $signingKey = (string) (env('JWT_SIGNING_KEY') ?: 'default_jwt_secret_key_32_characters_long');
    $now = time();

    try {
        DB::table('user')->updateOrInsert(
            ['id' => $userId],
            [
                'username' => $username,
                'password_hash' => 'hash_test_dummy_pass',
                'role' => $role,
            ]
        );
    } catch (\Throwable) {
    }

    $payload = [
        'sub' => $userId,
        'unique_name' => $username,
        'role' => $role,
        'jti' => 'test-jti',
        'iat' => $now,
        'exp' => $now + 3600,
    ];

    return JWT::encode($payload, $signingKey, 'HS256');
}

beforeEach(function (): void {
    DatabaseSandbox::init();
    DatabaseSandbox::cleanTables();
});
