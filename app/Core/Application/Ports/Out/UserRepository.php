<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

use App\Core\Domain\User;

interface UserRepository
{
    public function findByUsername(string $username): ?User;

    public function add(User $user): void;
}
