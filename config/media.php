<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Upload Size Limits
    |--------------------------------------------------------------------------
    |
    | Sizes are in bytes.
    |
    */
    'max_upload_size' => (int) env('MEDIA_MAX_UPLOAD_SIZE', 10 * 1024 * 1024),
    'max_image_upload_size' => (int) env('MEDIA_MAX_IMAGE_UPLOAD_SIZE', 5 * 1024 * 1024),
    'chunk_threshold' => (int) env('MEDIA_CHUNK_THRESHOLD', 2 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Allowed Extensions By Type
    |--------------------------------------------------------------------------
    */
    'allowed_types' => [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp', 'jfif', 'avif', 'heic', 'heif'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'webm'],
        'audio' => ['mp3', 'wav', 'ogg', 'flac'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Conversions
    |--------------------------------------------------------------------------
    */
    'conversions' => [
        'thumbnail' => ['width' => 150, 'height' => 150, 'mode' => 'cover'],
        'small' => ['width' => 300, 'height' => 300, 'mode' => 'contain'],
        'medium' => ['width' => 800, 'height' => 800, 'mode' => 'contain'],
        'large' => ['width' => 1920, 'height' => 1920, 'mode' => 'contain'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */
    'disk' => env('MEDIA_DISK', 'public'),
    'path_prefix' => env('MEDIA_PATH_PREFIX', 'uploads'),
    'generate_webp' => (bool) env('MEDIA_GENERATE_WEBP', true),
    'convert_original_images_to_webp' => (bool) env('MEDIA_CONVERT_ORIGINAL_IMAGES_TO_WEBP', true),
    'webp_quality' => (int) env('MEDIA_WEBP_QUALITY', 85),
];
