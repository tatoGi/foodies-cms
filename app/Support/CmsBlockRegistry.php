<?php

declare(strict_types=1);

namespace App\Support;

class CmsBlockRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            self::definition(
                key: 'page_hero',
                scope: 'page',
                icon: 'bi-stars',
                labels: ['en' => 'Hero'],
                descriptions: ['en' => 'Main heading and banner content'],
                fields: [
                    self::field('banner_top_title', 'text', ['en' => 'Top Title']),
                    self::field('banner_title', 'text', ['en' => 'Title']),
                    self::field('banner_description', 'textarea', ['en' => 'Description']),
                    self::field(
                        'banner_image',
                        'image',
                        ['en' => 'Banner Image', 'ka' => 'ბანერის სურათი'],
                        [
                            'en' => 'Recommended size: 1920x800px. Keep important logos and text within the center 60% safe zone.',
                            'ka' => 'რეკომენდებული ზომა: 1920x800px. მნიშვნელოვანი ლოგო და ტექსტი მოათავსეთ ცენტრალურ 60%-იან უსაფრთხო ზონაში.',
                        ]
                    ),
                    self::field(
                        'background_video',
                        'video',
                        ['en' => 'Background Video', 'ka' => 'ფონური ვიდეო'],
                        [
                            'en' => 'Optional looping background video (mp4 / webm). When set, it replaces the banner image. Recommended: muted, 1920x1080, under 8 MB.',
                            'ka' => 'არასავალდებულო ციკლური ფონური ვიდეო (mp4 / webm). მითითების შემთხვევაში ჩაანაცვლებს ბანერის სურათს. რეკომენდებული: ხმის გარეშე, 1920x1080, 8 MB-მდე.',
                        ]
                    ),
                    self::field(
                        'image_fit',
                        'select',
                        ['en' => 'Image Fit Style', 'ka' => 'სურათის მორგების სტილი'],
                        [
                            'en' => 'Background Cover fills the screen (may crop edges). Contain fits the whole image without cropping (perfect for logos).',
                            'ka' => 'Cover - ავსებს მთელ ფონს (შეიძლება ჩამოიჭრას). Contain - აჩვენებს სრულ სურათს/ლოგოს ჩამოჭრის გარეშე.',
                        ],
                        'cover',
                        [
                            'cover' => 'Background Cover (მოჭრილი ფონი)',
                            'contain' => 'Contain Logo/Image (სრული სურათი/ლოგო)',
                        ]
                    ),
                    self::field('button_title', 'text', ['en' => 'Button Title']),
                    self::field('redirect_link', 'text', ['en' => 'Redirect Link']),
                    self::field('cta_primary_text', 'text', ['en' => 'Primary CTA Text']),
                    self::field('cta_primary_url', 'text', ['en' => 'Primary CTA URL']),
                    self::field('cta_secondary_text', 'text', ['en' => 'Secondary CTA Text']),
                    self::field('cta_secondary_url', 'text', ['en' => 'Secondary CTA URL']),
                    self::field('year', 'text', ['en' => 'Year']),
                    self::field('location', 'text', ['en' => 'Location']),
                    self::field('category', 'text', ['en' => 'Category']),
                ],
                sortOrder: 10,
            ),
            self::definition(
                key: 'page_services',
                scope: 'page',
                icon: 'bi-grid-3x3-gap',
                labels: ['en' => 'Services / Items'],
                descriptions: ['en' => 'List-style block for services, categories, or project details'],
                fields: [
                    self::field('section_title', 'text', ['en' => 'Section Title']),
                    self::field('section_subtitle', 'text', ['en' => 'Section Subtitle']),
                    self::repeaterField('items', ['en' => 'Items'], [
                        self::field('title', 'text', ['en' => 'Title']),
                        self::field('description', 'textarea', ['en' => 'Description']),
                        self::field('url', 'text', ['en' => 'URL']),
                    ], addButtonLabels: ['en' => 'Add item']),
                ],
                sortOrder: 20,
            ),
            self::definition(
                key: 'page_process',
                scope: 'page',
                icon: 'bi-diagram-3',
                labels: ['en' => 'Process'],
                descriptions: ['en' => 'Step-by-step process block'],
                fields: [
                    self::field('section_title', 'text', ['en' => 'Section Title']),
                    self::repeaterField('steps', ['en' => 'Steps'], [
                        self::field('title', 'text', ['en' => 'Title']),
                        self::field('description', 'textarea', ['en' => 'Description']),
                    ], addButtonLabels: ['en' => 'Add step']),
                ],
                sortOrder: 30,
            ),
            self::definition(
                key: 'page_image_text',
                scope: 'page',
                icon: 'bi-card-image',
                labels: ['en' => 'Image + Text'],
                descriptions: ['en' => 'Single image block with title and text content'],
                fields: [
                    self::field('section_title', 'text', ['en' => 'Section Title']),
                    self::field('image', 'image', ['en' => 'Image']),
                    self::field('content_title', 'text', ['en' => 'Content Title']),
                    self::field('content_text', 'textarea', ['en' => 'Content Text']),
                ],
                sortOrder: 35,
            ),
            self::definition(
                key: 'page_gallery',
                scope: 'page',
                icon: 'bi-images',
                labels: ['en' => 'Gallery'],
                descriptions: ['en' => 'Multiple images upload block'],
                fields: [
                    self::field('section_title', 'text', ['en' => 'Section Title']),
                    self::field('section_description', 'textarea', ['en' => 'Section Description']),
                    self::field('images', 'gallery', ['en' => 'Images']),
                ],
                sortOrder: 36,
            ),
            self::definition(
                key: 'page_cta_banner',
                scope: 'page',
                icon: 'bi-megaphone',
                labels: ['en' => 'CTA Banner'],
                descriptions: ['en' => 'Call-to-action section'],
                fields: [
                    self::field('title', 'text', ['en' => 'Title']),
                    self::field('description', 'textarea', ['en' => 'Description']),
                    self::field('cta_text', 'text', ['en' => 'CTA Text']),
                    self::field('cta_url', 'text', ['en' => 'CTA URL']),
                ],
                sortOrder: 40,
            ),
            self::definition(
                key: 'page_text_content',
                scope: 'page',
                icon: 'bi-text-left',
                labels: ['en' => 'Text Content', 'ka' => 'ტექსტური კონტენტი'],
                descriptions: ['en' => 'Simple block with text and textarea fields for translatable page content', 'ka' => 'მარტივი ბლოკი ტექსტისა და ტექსტარეა ველებით'],
                fields: [
                    self::field('title', 'text', ['en' => 'Title', 'ka' => 'სათაური']),
                    self::field('subtitle', 'text', ['en' => 'Subtitle', 'ka' => 'ქვესათაური']),
                    self::field('content', 'textarea', ['en' => 'Content', 'ka' => 'კონტენტი']),
                    self::field('note', 'textarea', ['en' => 'Note', 'ka' => 'შენიშვნა']),
                ],
                sortOrder: 50,
            ),
            self::definition(
                key: 'post_intro',
                scope: 'post',
                icon: 'bi-card-text',
                labels: ['en' => 'Post Intro'],
                descriptions: ['en' => 'Intro metadata for a post'],
                fields: [
                    self::field('category', 'text', ['en' => 'Category']),
                    self::field('read_time', 'text', ['en' => 'Read Time']),
                    self::field('intro_text', 'textarea', ['en' => 'Intro Text']),
                    self::field('intro_image', 'image', ['en' => 'Intro Image']),
                ],
                sortOrder: 110,
            ),
            self::definition(
                key: 'post_checklist',
                scope: 'post',
                icon: 'bi-list-check',
                labels: ['en' => 'Checklist'],
                descriptions: ['en' => 'Checklist block for posts'],
                fields: [
                    self::field('title', 'text', ['en' => 'Title']),
                    self::repeaterField('items', ['en' => 'Items'], [
                        self::field('text', 'text', ['en' => 'Text']),
                    ], addButtonLabels: ['en' => 'Add item']),
                ],
                sortOrder: 120,
            ),
            self::definition(
                key: 'post_quote',
                scope: 'post',
                icon: 'bi-chat-quote',
                labels: ['en' => 'Quote'],
                descriptions: ['en' => 'Highlighted quote block'],
                fields: [
                    self::field('text', 'textarea', ['en' => 'Quote Text']),
                    self::field('author', 'text', ['en' => 'Author']),
                ],
                sortOrder: 130,
            ),
            self::definition(
                key: 'product_intro',
                scope: 'product',
                icon: 'bi-bag',
                labels: ['en' => 'Product Intro'],
                descriptions: ['en' => 'Primary product metadata block'],
                fields: [
                    self::field('category', 'text', ['en' => 'Category']),
                    self::field('material', 'text', ['en' => 'Material']),
                    self::field('on_sale', 'select', ['en' => 'On Sale']),
                    self::repeaterField('colors', ['en' => 'Colors'], [
                        self::field('value', 'color', ['en' => 'Color']),
                    ], addButtonLabels: ['en' => 'Add color']),
                ],
                sortOrder: 210,
            ),
            self::definition(
                key: 'product_gallery',
                scope: 'product',
                icon: 'bi-images',
                labels: ['en' => 'Product Gallery'],
                descriptions: ['en' => 'Image gallery for product pages'],
                fields: [
                    self::field('images', 'gallery', ['en' => 'Images']),
                ],
                sortOrder: 220,
            ),
            self::definition(
                key: 'product_specs',
                scope: 'product',
                icon: 'bi-sliders',
                labels: ['en' => 'Product Specs'],
                descriptions: ['en' => 'Structured product specifications'],
                fields: [
                    self::repeaterField('items', ['en' => 'Specifications'], [
                        self::field('label', 'text', ['en' => 'Label']),
                        self::field('value', 'text', ['en' => 'Value']),
                    ], addButtonLabels: ['en' => 'Add specification']),
                ],
                sortOrder: 230,
            ),
        ];
    }

    /**
     * @param  array<string, string>  $labels
     * @param  array<string, string>  $descriptions
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private static function definition(
        string $key,
        string $scope,
        string $icon,
        array $labels,
        array $descriptions,
        array $fields,
        int $sortOrder,
    ): array {
        return [
            'key' => $key,
            'scope' => $scope,
            'icon' => $icon,
            'labels' => $labels,
            'descriptions' => $descriptions,
            'fields' => $fields,
            'sort_order' => $sortOrder,
            'is_enabled' => true,
        ];
    }

    /**
     * @param  array<string, string>  $labels
     * @param  array<string, string>  $helps
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    private static function field(
        string $key,
        string $type,
        array $labels,
        array $helps = [],
        mixed $default = '',
        array $options = [],
    ): array {
        return [
            'key' => $key,
            'type' => $type,
            'label' => (string) ($labels['en'] ?? $key),
            'labels' => $labels,
            'help' => (string) ($helps['en'] ?? ''),
            'helps' => $helps,
            'options' => $options,
            'default' => $default,
        ];
    }

    /**
     * @param  array<string, string>  $labels
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, string>  $helps
     * @param  array<string, string>  $addButtonLabels
     * @return array<string, mixed>
     */
    private static function repeaterField(
        string $key,
        array $labels,
        array $fields,
        array $helps = [],
        array $addButtonLabels = [],
        array $default = [],
    ): array {
        return [
            'key' => $key,
            'type' => 'repeater',
            'label' => (string) ($labels['en'] ?? $key),
            'labels' => $labels,
            'help' => (string) ($helps['en'] ?? ''),
            'helps' => $helps,
            'fields' => $fields,
            'add_button_label' => (string) ($addButtonLabels['en'] ?? 'Add item'),
            'add_button_labels' => $addButtonLabels,
            'default' => $default,
        ];
    }
}
