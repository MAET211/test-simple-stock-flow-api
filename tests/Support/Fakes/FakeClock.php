<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Ports\Out\Clock;
use DateTimeImmutable;
use DateTimeZone;

final class FakeClock implements Clock
{
    private DateTimeImmutable $now;

    public function __construct(?DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new DateTimeImmutable('2026-10-03 12:00:00', new DateTimeZone('UTC'));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function setNow(DateTimeImmutable $now): void
    {
        $this->now = $now->setTimezone(new DateTimeZone('UTC'));
    }
}
