<?php

declare(strict_types=1);

namespace App\Core\Application\Exceptions;

use App\Core\Domain\Exceptions\BusinessRuleViolation;

final class ImageTypeNotAllowed extends BusinessRuleViolation
{
    public function __construct(string $contentType)
    {
        parent::__construct("Tipo de archivo no permitido: {$contentType}.");
    }
}
