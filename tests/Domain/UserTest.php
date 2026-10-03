<?php

declare(strict_types=1);

use App\Core\Domain\Exceptions\InvalidRole;
use App\Core\Domain\Exceptions\UsernameRequired;
use App\Core\Domain\Roles;
use App\Core\Domain\User;
use App\Core\Domain\ValueObjects\UserId;

function registerUser(string $username = 'ana', string $role = Roles::SELLER, string $hash = '$argon2id$v=19$x'): User
{
    return User::register(UserId::generate(), $username, $hash, $role);
}

it('rn_10 stores the username trimmed and in lower case', function (): void {
    expect(registerUser('  Ana  ')->username())->toBe('ana');
});

it('rn_10 lower-cases accented characters too', function (): void {
    expect(User::normalizeUsername('  JOSÉ '))->toBe('josé');
});

it('rejects a blank username', function (): void {
    expect(fn () => registerUser('   '))->toThrow(UsernameRequired::class);
});

it('rn_11 accepts the two valid roles', function (): void {
    expect(registerUser('a', 'admin')->role())->toBe('admin')
        ->and(registerUser('b', 'seller')->role())->toBe('seller');
});

it('rn_11 rejects a role outside the closed set', function (string $role): void {
    expect(fn () => registerUser('ana', $role))->toThrow(InvalidRole::class);
})->with(['empty' => [''], 'upper case' => ['ADMIN'], 'unknown' => ['root'], 'padded' => [' seller']]);

it('rn_11 reports the rejected role in the message', function (): void {
    expect(fn () => registerUser('ana', 'ADMIN'))->toThrow(InvalidRole::class, "Rol no válido: 'ADMIN'.");
});

it('rejects a blank password hash', function (): void {
    expect(fn () => registerUser('ana', 'seller', '  '))->toThrow(InvalidArgumentException::class);
});
