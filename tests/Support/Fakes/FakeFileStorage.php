<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Core\Application\Ports\Out\FileStorage;

final class FakeFileStorage implements FileStorage
{
    /** @var array<string, array{bytes: string, contentType: string}> */
    private array $storage = [];

    public function save(string $bytes, string $contentType): string
    {
        $ext = match ($contentType) {
            'image/png' => '.png',
            'image/webp' => '.webp',
            default => '.jpg',
        };

        $key = bin2hex(random_bytes(16)).$ext;
        $this->storage[$key] = [
            'bytes' => $bytes,
            'contentType' => $contentType,
        ];

        return $key;
    }

    public function delete(string $key): void
    {
        unset($this->storage[$key]);
    }

    public function urlFor(string $key): string
    {
        return "/media/{$key}";
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }
}
