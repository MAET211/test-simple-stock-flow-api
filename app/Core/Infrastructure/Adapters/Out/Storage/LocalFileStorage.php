<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Storage;

use App\Core\Application\Ports\Out\FileStorage;

final class LocalFileStorage implements FileStorage
{
    private string $mediaRoot;

    public function __construct(?string $mediaRoot = null)
    {
        $rootVal = $mediaRoot ?? getenv('MEDIA_ROOT');
        $this->mediaRoot = is_string($rootVal) && $rootVal !== '' ? $rootVal : '/var/media';
    }

    public function save(string $bytes, string $contentType): string
    {
        $ext = match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };

        $key = bin2hex(random_bytes(16)).'.'.$ext;

        if (! is_dir($this->mediaRoot)) {
            mkdir($this->mediaRoot, 0777, true);
        }

        $filePath = $this->mediaRoot.DIRECTORY_SEPARATOR.$key;
        file_put_contents($filePath, $bytes);

        return $key;
    }

    public function delete(string $key): void
    {
        $filePath = $this->mediaRoot.DIRECTORY_SEPARATOR.$key;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public function urlFor(string $key): string
    {
        return '/media/'.$key;
    }

    public function has(string $key): bool
    {
        return file_exists($this->mediaRoot.DIRECTORY_SEPARATOR.$key);
    }

    public function read(string $key): ?string
    {
        $filePath = $this->mediaRoot.DIRECTORY_SEPARATOR.$key;
        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        return $content !== false ? $content : null;
    }

    public function mimeType(string $key): ?string
    {
        if (str_ends_with($key, '.jpg') || str_ends_with($key, '.jpeg')) {
            return 'image/jpeg';
        }
        if (str_ends_with($key, '.png')) {
            return 'image/png';
        }
        if (str_ends_with($key, '.webp')) {
            return 'image/webp';
        }

        return null;
    }
}
