<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Exceptions\ConcurrencyConflict;
use App\Core\Application\Ports\Out\UnitOfWork;

final class FakeUnitOfWork implements UnitOfWork
{
    public int $commitCount = 0;

    public int $discardCount = 0;

    public ?ConcurrencyConflict $conflictToThrow = null;

    public function commit(): void
    {
        if ($this->conflictToThrow !== null) {
            $e = $this->conflictToThrow;
            $this->conflictToThrow = null;
            throw $e;
        }

        $this->commitCount++;
    }

    public function discardChanges(): void
    {
        $this->discardCount++;
    }
}
