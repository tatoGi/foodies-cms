<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlockTypeDefinition;
use App\Models\PageTemplate;
use Illuminate\Database\Seeder;

/**
 * Code-owned block types for the site's designed sections and the page templates that use them.
 * Safe to re-run: definitions are updated in place. See docs/superpowers/specs/2026-09-25-cms-page-templates-design.md.
 */
class SitePageTemplateSeeder extends Seeder
{
    /** Block order of the About page as designed on the site. */
    public const ABOUT_BLOCKS = [
        'about_why_choose_us',
        'about_discount_food',
        'about_food_menu',
        'about_gallery',
        'best_delivery',
        'about_discount_banner',
        'about_news',
    ];

    /** Every site template: names and the block types it allows, in display order. */
    public const TEMPLATES = [
        'about' => ['ka' => 'ჩვენ შესახებ', 'en' => 'About', 'blocks' => self::ABOUT_BLOCKS],
        'contact' => ['ka' => 'კონტაქტი', 'en' => 'Contact', 'blocks' => ['contact_locations', 'contact_map']],
        'faq' => ['ka' => 'ხშირი კითხვები', 'en' => 'FAQ', 'blocks' => ['faq_accordion']],
        'gallery' => ['ka' => 'გალერეა', 'en' => 'Gallery', 'blocks' => ['gallery_grid']],
        'history' => ['ka' => 'ისტორია', 'en' => 'History', 'blocks' => ['history_top', 'history_timeline']],
        'reservation' => ['ka' => 'ჯავშანი', 'en' => 'Reservation', 'blocks' => ['reservation_feature', 'reservation_combo_offer', 'brand_strip']],
        'menu' => ['ka' => 'მენიუ', 'en' => 'Menu', 'blocks' => ['menu_full', 'menu_special_banner', 'menu_best_selling', 'menu_best_food']],
    ];

    public function run(): void
    {
        foreach ($this->blocks() as $index => $block) {
            $fields = $block['fields'];
            BlockTypeDefinition::query()->updateOrCreate(
                ['key' => $block['key']],
                [
                    'label' => $block['ka'],
                    'description' => $block['en'],
                    'scope' => 'page',
                    'icon' => 'bi-layout-text-window',
                    'schema' => [
                        'fields' => $fields,
                        'translations' => [
                            'labels' => ['ka' => $block['ka'], 'en' => $block['en']],
                            'descriptions' => ['ka' => $block['ka'], 'en' => $block['en']],
                        ],
                    ],
                    'default_data' => collect($fields)->mapWithKeys(fn (array $field): array => [$field['key'] => $field['default']])->all(),
                    'is_system' => true,
                    'is_enabled' => true,
                    'sort_order' => 100 + $index,
                ],
            );
        }

        foreach (self::TEMPLATES as $slug => $names) {
            $template = PageTemplate::query()->firstOrCreate(['slug' => $slug]);
            foreach (['ka', 'en'] as $locale) {
                $template->translations()->updateOrCreate(['locale' => $locale], ['name' => $names[$locale]]);
            }
        }
    }

    /** @return list<array{key: string, ka: string, en: string, fields: list<array<string, mixed>>}> */
    private function blocks(): array
    {
        $buttons = [
            $this->field('button_text', 'text', 'ღილაკის ტექსტი', 'Button text'),
            $this->field('button_link', 'text', 'ღილაკის ბმული', 'Button link'),
        ];

        return [
            [
                'key' => 'about_why_choose_us', 'ka' => 'რატომ ჩვენ', 'en' => 'Why choose us',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('title_line2', 'text', 'სათაური (მე-2 ხაზი)', 'Title (line 2)'),
                    $this->field('description', 'textarea', 'აღწერა', 'Description'),
                    $this->repeater('list_one', 'სია 1', 'List 1', [$this->field('text', 'text', 'ტექსტი', 'Text')]),
                    $this->repeater('list_two', 'სია 2', 'List 2', [$this->field('text', 'text', 'ტექსტი', 'Text')]),
                    $this->field('image', 'image', 'სურათი', 'Image'),
                    $this->field('primary_button_text', 'text', 'მთავარი ღილაკი', 'Primary button'),
                    $this->field('primary_button_link', 'text', 'მთავარი ღილაკის ბმული', 'Primary button link'),
                    $this->field('secondary_button_text', 'text', 'მეორე ღილაკი', 'Secondary button'),
                    $this->field('secondary_button_link', 'text', 'მეორე ღილაკის ბმული', 'Secondary button link'),
                ],
            ],
            [
                'key' => 'about_discount_food', 'ka' => 'ფასდაკლების ბანერები', 'en' => 'Discount banners',
                'fields' => [
                    $this->field('banner1_label', 'text', 'ბანერი 1: წარწერა', 'Banner 1: label'),
                    $this->field('banner1_title', 'text', 'ბანერი 1: სათაური', 'Banner 1: title'),
                    $this->field('banner1_image', 'image', 'ბანერი 1: ფონი', 'Banner 1: background'),
                    $this->field('banner2_label', 'text', 'ბანერი 2: წარწერა', 'Banner 2: label'),
                    $this->field('banner2_title', 'text', 'ბანერი 2: სათაური', 'Banner 2: title'),
                    $this->field('banner2_accent', 'text', 'ბანერი 2: აქცენტი', 'Banner 2: accent'),
                    $this->field('banner2_text', 'text', 'ბანერი 2: ტექსტი', 'Banner 2: text'),
                    $this->field('banner2_price_label', 'text', 'ბანერი 2: ფასის წარწერა', 'Banner 2: price label'),
                    $this->field('banner2_price', 'text', 'ბანერი 2: ფასი', 'Banner 2: price'),
                    $this->field('banner2_image', 'image', 'ბანერი 2: კერძის სურათი', 'Banner 2: dish image'),
                    $this->field('banner2_background', 'image', 'ბანერი 2: ფონი', 'Banner 2: background'),
                    $this->field('banner3_title', 'textarea', 'ბანერი 3: სათაური (ხაზებად)', 'Banner 3: title (one line each)'),
                    $this->field('banner3_image', 'image', 'ბანერი 3: სურათი', 'Banner 3: image'),
                    $this->field('banner4_label', 'text', 'ბანერი 4: წარწერა', 'Banner 4: label'),
                    $this->field('banner4_title', 'text', 'ბანერი 4: სათაური', 'Banner 4: title'),
                    $this->field('banner4_subtitle', 'text', 'ბანერი 4: ქვესათაური', 'Banner 4: subtitle'),
                    $this->field('banner4_image', 'image', 'ბანერი 4: კერძის სურათი', 'Banner 4: dish image'),
                    $this->field('banner4_background', 'image', 'ბანერი 4: ფონი', 'Banner 4: background'),
                    ...$buttons,
                ],
            ],
            [
                'key' => 'about_food_menu', 'ka' => 'საუკეთესო კერძები (მენიუდან)', 'en' => 'Best dishes (from the menu)',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('left_image', 'image', 'მარცხენა სურათი', 'Left image'),
                    $this->field('right_image', 'image', 'მარჯვენა სურათი', 'Right image'),
                    ...$buttons,
                ],
            ],
            [
                'key' => 'about_gallery', 'ka' => 'გალერეის სლაიდერი', 'en' => 'Gallery slider',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('images', 'gallery', 'სურათები', 'Images'),
                    ...$buttons,
                ],
            ],
            [
                'key' => 'best_delivery', 'ka' => 'მიწოდება და ქულები', 'en' => 'Delivery and points',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('description', 'textarea', 'აღწერა', 'Description'),
                    $this->field('points_sub_title', 'text', 'ქულები: ქვესათაური', 'Points: subtitle'),
                    $this->field('points_title', 'text', 'ქულები: სათაური', 'Points: title'),
                    $this->field('info_text', 'textarea', 'ინფო ტექსტი', 'Info text'),
                    $this->field('image', 'image', 'სურათი', 'Image'),
                    ...$buttons,
                ],
            ],
            [
                'key' => 'about_discount_banner', 'ka' => 'ფასდაკლების დიდი ბანერი', 'en' => 'Discount hero banner',
                'fields' => [
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('description', 'textarea', 'აღწერა', 'Description'),
                    $this->field('discount_text', 'text', 'ფასდაკლების ტექსტი', 'Discount text'),
                    $this->field('right_title', 'text', 'მარჯვენა სათაური', 'Right title'),
                    $this->field('background_image', 'image', 'ფონი', 'Background'),
                    ...$buttons,
                ],
            ],
            [
                'key' => 'about_news', 'ka' => 'სიახლეები (ბლოგიდან)', 'en' => 'News (from the blog)',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                ],
            ],
            [
                'key' => 'contact_locations', 'ka' => 'მისამართები', 'en' => 'Locations',
                'fields' => [
                    $this->repeater('locations', 'მისამართები', 'Locations', [
                        $this->field('icon', 'image', 'ხატულა', 'Icon'),
                        $this->field('title', 'text', 'სათაური', 'Title'),
                        $this->field('find_us_label', 'text', 'მისამართის წარწერა', 'Address label'),
                        $this->field('address', 'text', 'მისამართი', 'Address'),
                        $this->field('mail_us_label', 'text', 'ელფოსტის წარწერა', 'Email label'),
                        $this->field('email', 'text', 'ელფოსტა', 'Email'),
                        $this->field('call_us_label', 'text', 'ტელეფონის წარწერა', 'Phone label'),
                        $this->field('phone', 'text', 'ტელეფონი', 'Phone'),
                    ]),
                ],
            ],
            [
                'key' => 'contact_map', 'ka' => 'რუკა და ფორმა', 'en' => 'Map and form',
                'fields' => [
                    $this->field('map_embed_url', 'text', 'Google Maps embed ბმული', 'Google Maps embed URL'),
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('description', 'textarea', 'აღწერა', 'Description'),
                ],
            ],
            [
                'key' => 'faq_accordion', 'ka' => 'კითხვა-პასუხი', 'en' => 'Questions and answers',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->repeater('items', 'კითხვები', 'Questions', [
                        $this->field('question', 'text', 'კითხვა', 'Question'),
                        $this->field('answer', 'textarea', 'პასუხი', 'Answer'),
                    ]),
                ],
            ],
            [
                'key' => 'gallery_grid', 'ka' => 'გალერეის ბადე', 'en' => 'Gallery grid',
                'fields' => [
                    $this->field('images', 'gallery', 'სურათები (14 ადგილი)', 'Images (14 slots)'),
                ],
            ],
            [
                'key' => 'history_top', 'ka' => 'ისტორიის შესავალი', 'en' => 'History intro',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('description', 'textarea', 'აღწერა', 'Description'),
                    $this->field('image', 'image', 'ფოტო', 'Photo'),
                    $this->field('signature_image', 'image', 'ხელმოწერა', 'Signature'),
                ],
            ],
            [
                'key' => 'history_timeline', 'ka' => 'ისტორიის ქრონოლოგია', 'en' => 'History timeline',
                'fields' => [
                    $this->repeater('entries', 'წლები', 'Years', [
                        $this->field('year', 'text', 'წელი', 'Year'),
                        $this->field('title', 'text', 'სათაური', 'Title'),
                        $this->field('text', 'textarea', 'ტექსტი', 'Text'),
                        $this->field('image', 'image', 'სურათი', 'Image'),
                    ]),
                ],
            ],
            [
                'key' => 'reservation_feature', 'ka' => 'უპირატესობები', 'en' => 'Features',
                'fields' => [
                    $this->repeater('items', 'უპირატესობები', 'Features', [
                        $this->field('icon', 'image', 'ხატულა', 'Icon'),
                        $this->field('title', 'text', 'სათაური', 'Title'),
                        $this->field('description', 'textarea', 'აღწერა', 'Description'),
                    ]),
                    ...$buttons,
                ],
            ],
            [
                'key' => 'reservation_combo_offer', 'ka' => 'ჯავშნის შეთავაზება', 'en' => 'Booking offer',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('description', 'textarea', 'აღწერა', 'Description'),
                    $this->field('support_label', 'text', 'მხარდაჭერის წარწერა', 'Support label'),
                    $this->field('support_phone', 'text', 'მხარდაჭერის ტელეფონი', 'Support phone'),
                    $this->field('background_image', 'image', 'ფონი', 'Background'),
                    $this->field('form_title', 'text', 'ფორმის სათაური', 'Form title'),
                    $this->field('form_description', 'textarea', 'ფორმის აღწერა', 'Form description'),
                ],
            ],
            [
                'key' => 'brand_strip', 'ka' => 'ბრენდების ლოგოები', 'en' => 'Brand logos',
                'fields' => [
                    $this->field('logos', 'gallery', 'ლოგოები', 'Logos'),
                ],
            ],
            [
                'key' => 'menu_full', 'ka' => 'სრული მენიუ (სალაროდან)', 'en' => 'Full menu (from the POS)',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                ],
            ],
            [
                'key' => 'menu_special_banner', 'ka' => 'სპეციალური შეთავაზების ბანერი', 'en' => 'Special offer banner',
                'fields' => [
                    $this->field('sub_text', 'text', 'წარწერა', 'Label'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('text', 'textarea', 'ტექსტი', 'Text'),
                    $this->field('image', 'image', 'კერძის სურათი', 'Dish image'),
                    $this->field('background_image', 'image', 'ფონი', 'Background'),
                    ...$buttons,
                ],
            ],
            [
                'key' => 'menu_best_selling', 'ka' => 'ყველაზე გაყიდვადი (გამორჩეულიდან)', 'en' => 'Best selling (from featured)',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                ],
            ],
            [
                'key' => 'menu_best_food', 'ka' => 'საუკეთესო მენიუ (გამორჩეულიდან)', 'en' => 'Best food menu (from featured)',
                'fields' => [
                    $this->field('sub_title', 'text', 'ქვესათაური', 'Subtitle'),
                    $this->field('title', 'text', 'სათაური', 'Title'),
                    $this->field('featured_name', 'text', 'ცენტრალური კერძი', 'Centre dish'),
                    $this->field('featured_tagline', 'text', 'ცენტრალური კერძის ტექსტი', 'Centre dish tagline'),
                    $this->field('featured_price', 'text', 'ფასი', 'Price'),
                    $this->field('featured_old_price', 'text', 'ძველი ფასი', 'Old price'),
                    $this->field('background_image', 'image', 'ფონი', 'Background'),
                    $this->field('bottom_text', 'textarea', 'ქვედა ტექსტი', 'Bottom text'),
                    ...$buttons,
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function field(string $key, string $type, string $ka, string $en): array
    {
        return [
            'key' => $key,
            'type' => $type,
            'label' => $ka,
            'labels' => ['ka' => $ka, 'en' => $en],
            'help' => '',
            'helps' => [],
            'default' => '',
            'options' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function repeater(string $key, string $ka, string $en, array $fields): array
    {
        return array_merge($this->field($key, 'repeater', $ka, $en), [
            'default' => [],
            'fields' => $fields,
            'add_button_label' => 'დამატება',
            'add_button_labels' => ['ka' => 'დამატება', 'en' => 'Add item'],
        ]);
    }
}
