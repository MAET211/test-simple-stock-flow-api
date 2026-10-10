<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Controllers;

use App\Core\Infrastructure\Adapters\Out\Storage\LocalFileStorage;
use Illuminate\Http\Response;

final class MediaController
{
    public function show(string $key, LocalFileStorage $storage): Response
    {
        // Must match 32 hex chars + extension
        if (preg_match('/^[0-9a-f]{32}\.(jpg|jpeg|png|webp)$/i', $key) !== 1) {
            return new Response('', 404, ['Content-Length' => '0']);
        }

        if (! $storage->has($key)) {
            return new Response('', 404, ['Content-Length' => '0']);
        }

        $content = $storage->read($key);
        if ($content === null) {
            return new Response('', 404, ['Content-Length' => '0']);
        }

        $mimeType = $storage->mimeType($key) ?? 'application/octet-stream';

        return new Response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) strlen($content),
        ]);
    }
}
