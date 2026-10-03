<?php

declare(strict_types=1);

namespace App\Core\Domain;

/**
 * Closed set of roles. Case-sensitive: "ADMIN" is not valid (RN-11).
 */
final class Roles
{
    public const ADMIN = 'admin';

    public const SELLER = 'seller';

    public static function isValid(string $role): bool
    {
        return $role === self::ADMIN || $role === self::SELLER;
    }
}
