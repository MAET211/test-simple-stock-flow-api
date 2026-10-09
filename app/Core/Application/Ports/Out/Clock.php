<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
