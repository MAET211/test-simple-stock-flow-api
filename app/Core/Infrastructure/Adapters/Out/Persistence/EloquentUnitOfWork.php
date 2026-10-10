<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence;

use App\Core\Application\Ports\Out\UnitOfWork;
use Illuminate\Support\Facades\DB;

final class EloquentUnitOfWork implements UnitOfWork
{
    public function commit(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::commit();
        }
    }

    public function discardChanges(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        DB::beginTransaction();
    }
}
