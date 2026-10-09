<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

interface UnitOfWork
{
    public function commit(): void;

    public function discardChanges(): void;
}
