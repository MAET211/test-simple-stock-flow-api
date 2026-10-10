<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Clock;

use App\Core\Application\Ports\Out\Clock;
use DateTimeImmutable;
use DateTimeZone;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
