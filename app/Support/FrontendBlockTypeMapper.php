<?php

declare(strict_types=1);

namespace App\Support;

class FrontendBlockTypeMapper
{
    public static function toFrontend(string $type): string
    {
        return match (trim($type)) {
            'page_hero', 'banner' => 'main_banner',
            'page_services' => 'items_grid',
            'page_process' => 'process_steps',
            'page_image_text' => 'image_text',
            'page_gallery' => 'photo_gallery',
            'page_cta_banner' => 'cta_banner',
            default => trim($type),
        };
    }
}
