<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Errors;

use Exception;

final class ValidationFailedException extends Exception
{
    /**
     * @param  array<string, array<string>>  $errors
     */
    public function __construct(
        public readonly array $errors,
        public readonly bool $isOnlyPathError = false
    ) {
        $fields = array_keys($errors);
        $detail = 'Datos de entrada no válidos: '.implode(', ', $fields).'.';
        parent::__construct($detail);
    }
}
