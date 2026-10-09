<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\In;

use App\Core\Application\Views\AuthResult;
use App\Core\Domain\ValueObjects\UserId;

interface Authenticate
{
    public function login(string $username, string $password): AuthResult;

    public function register(string $username, string $password, string $role): UserId;
}
