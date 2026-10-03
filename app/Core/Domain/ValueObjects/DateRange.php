<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObjects;

use App\Core\Domain\Exceptions\InvalidDateRange;

/**
 * Half-open range [from, to): "from" is inclusive, "to" is exclusive (D-C2). Always UTC.
 */
final class DateRange
{
    public readonly \DateTimeImmutable $from;

    public readonly \DateTimeImmutable $to;

    public function __construct(\DateTimeImmutable $from, \DateTimeImmutable $to)
    {
        if ($to < $from) {
            throw new InvalidDateRange;
        }

        $utc = new \DateTimeZone('UTC');
        $this->from = $from->setTimezone($utc);
        $this->to = $to->setTimezone($utc);
    }

    public function contains(\DateTimeImmutable $instant): bool
    {
        return $instant >= $this->from && $instant < $this->to;
    }
}
