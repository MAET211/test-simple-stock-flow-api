<?php

declare(strict_types=1);

namespace App\Core\Application\Exceptions;

use App\Core\Domain\Exceptions\BusinessRuleViolation;

final class ImageTooLarge extends BusinessRuleViolation
{
    public function __construct(string $message = 'La imagen supera el máximo de 5 MB.')
    {
        parent::__construct($message);
    }
}
