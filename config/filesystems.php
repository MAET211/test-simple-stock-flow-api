<?php

declare(strict_types=1);

return [
    'default' => 'media',
    'disks' => [
        'media' => [
            'driver' => 'local',
            'root' => env('MEDIA_ROOT', storage_path('app/media')),
            'throw' => false,
        ],
    ],
];
