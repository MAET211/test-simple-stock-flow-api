<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\In;

interface ProvisionAdmin
{
    public function ensureAdmin(string $username, string $password): void;
}
