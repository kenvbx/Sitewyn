<?php

return [
    'name' => 'Media',
    'disk' => env('MEDIA_DISK', 'public'),
    'upload_directory_format' => env('MEDIA_UPLOAD_DIRECTORY_FORMAT', 'Y/m'),
    'max_upload_size' => env('MEDIA_MAX_UPLOAD_SIZE', 10240),
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'text/plain',
        'application/zip',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'image_conversions' => [
        'thumb' => [
            'width' => 150,
            'height' => 150,
            'mode' => 'cover',
        ],
        'medium' => [
            'width' => 768,
            'height' => null,
            'mode' => 'scale_down',
        ],
    ],
];
