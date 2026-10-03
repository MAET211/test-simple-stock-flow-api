<?php

declare(strict_types=1);

return [
    'default' => 'sync',
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
    ],
    'batching' => [
        'database' => 'mysql',
        'table' => 'job_batches',
    ],
    'failed' => [
        'driver' => 'null',
    ],
];
