<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\In;

use App\Core\Application\Commands\PlaceSaleCommand;
use App\Core\Domain\ValueObjects\SaleId;

interface PlaceSale
{
    public function execute(PlaceSaleCommand $command): SaleId;
}
