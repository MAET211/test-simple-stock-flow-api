<?php

declare(strict_types=1);

namespace App\Core\Application\Ports\Out;

interface FileStorage
{
    public function save(string $bytes, string $contentType): string;

    public function delete(string $key): void;

    public function urlFor(string $key): string;
}
