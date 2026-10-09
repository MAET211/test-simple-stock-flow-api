<?php

declare(strict_types=1);

namespace App\Core\Application\Exceptions;

use RuntimeException;

final class NotFound extends RuntimeException
{
    public function __construct(string $message = 'El recurso no existe.')
    {
        parent::__construct($message);
    }
}
