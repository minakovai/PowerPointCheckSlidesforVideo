<?php

declare(strict_types=1);

return [
    'upload_max_bytes' => 100 * 1024 * 1024,
    'allowed_mime_types' => [
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/octet-stream',
    ],
    'video_zone' => [
        'unit' => 'percent',
        'anchor' => 'bottom-right',
        'width' => 30,
        'height' => 40,
        'offset_x' => 0,
        'offset_y' => 0,
    ],
    'preview' => [
        'enabled' => true,
        'max_width' => 1280,
        'max_height' => 720,
    ],
    'paths' => [
        'uploads' => __DIR__ . '/../storage/uploads',
        'previews' => __DIR__ . '/../storage/previews',
    ],
];
