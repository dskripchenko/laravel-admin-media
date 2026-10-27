<?php

declare(strict_types=1);

return [
    'disk' => env('ADMIN_MEDIA_DISK', 'public'),
    'path_prefix' => 'media',

    'allowed_mimes' => [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml',
        'application/pdf',
        'video/mp4', 'video/webm',
        'audio/mpeg', 'audio/wav',
    ],

    'max_size_mb' => 50,

    // Scanning of uploads before they are stored. `none`, or `clamd` — a
    // ClamAV daemon at `address` (tcp://host:3310 or unix:///run/clamav/clamd.ctl).
    // When the scanner cannot answer: `reject` the upload (safe) or `allow` it
    // unscanned with a warning in the log.
    'scanning' => [
        'driver' => env('ADMIN_MEDIA_SCANNER', 'none'),
        'clamd' => [
            'address' => env('ADMIN_MEDIA_CLAMD', 'tcp://127.0.0.1:3310'),
            'timeout' => (float) env('ADMIN_MEDIA_CLAMD_TIMEOUT', 30),
        ],
        'on_error' => env('ADMIN_MEDIA_SCAN_ON_ERROR', 'reject'),
    ],

    'collections' => [
        'default' => ['label' => 'Общая'],
        'articles' => ['label' => 'Статьи'],
        'avatars' => ['label' => 'Аватары'],
    ],

    'responsive_sets' => [
        'content' => [
            ['name' => 'thumb', 'width' => 200, 'format' => 'webp', 'quality' => 80],
            ['name' => 'w-768', 'width' => 768, 'format' => 'webp', 'quality' => 85],
            ['name' => 'w-1280', 'width' => 1280, 'format' => 'webp', 'quality' => 85],
        ],
        'avatar' => [
            ['name' => 'sm', 'width' => 64, 'height' => 64, 'crop' => true, 'format' => 'webp'],
            ['name' => 'md', 'width' => 128, 'height' => 128, 'crop' => true, 'format' => 'webp'],
        ],
    ],

    'image_processor' => [
        'driver' => 'auto',
        'strip_exif' => true,
        'auto_orient' => true,
    ],

    'cleanup' => [
        'orphan_after_days' => 30,
    ],
];
