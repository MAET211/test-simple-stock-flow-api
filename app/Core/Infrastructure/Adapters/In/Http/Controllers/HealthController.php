<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class HealthController
{
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok'], 200);
    }
}
