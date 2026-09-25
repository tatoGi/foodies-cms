<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\BlockTypeDefinition;
use App\Models\Page;
use App\Models\PageTemplate;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\PostTranslation;
use Database\Seeders\SiteDemoSeeder;
use Database\Seeders\SitePageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_seeder_creates_about_block_types_and_template_idempotently(): void
    {
        $this->seed(SitePageTemplateSeeder::class);
        $this->seed(SitePageTemplateSeeder::class);

        foreach (SitePageTemplateSeeder::ABOUT_BLOCKS as $key) {
            $definition = BlockTypeDefinition::query()->where('key', $key)->sole();
            $this->assertSame('page', $definition->scope);
            $this->assertTrue($definition->is_enabled);
            $this->assertNotEmpty($definition->schema['fields']);
        }

        $why = BlockTypeDefinition::query()->where('key', 'about_why_choose_us')->sole();
        $listOne = collect($why->schema['fields'])->firstWhere('key', 'list_one');
        $this->assertSame('repeater', $listOne['type']);
        $this->assertSame('text', $listOne['fields'][0]['key']);

        $template = PageTemplate::query()->where('slug', 'about')->sole();
        $this->assertSame('ჩვენ შესახებ', $template->translations()->where('locale', 'ka')->value('name'));
    }

    public function test_template_seeder_creates_every_template_and_its_block_types(): void
    {
        $this->seed(SitePageTemplateSeeder::class);

        foreach (SitePageTemplateSeeder::TEMPLATES as $slug => $template) {
            $this->assertTrue(PageTemplate::query()->where('slug', $slug)->exists(), $slug);
            foreach ($template['blocks'] as $key) {
                $this->assertTrue(BlockTypeDefinition::query()->where('key', $key)->where('is_enabled', true)->exists(), $key);
            }
        }

        $faq = BlockTypeDefinition::query()->where('key', 'faq_accordion')->sole();
        $items = collect($faq->schema['fields'])->firstWhere('key', 'items');
        $this->assertSame(['question', 'answer'], array_column($items['fields'], 'key'));
    }

    public function test_demo_seeder_creates_the_about_page_with_blocks_and_images(): void
    {
        Storage::fake('public');
        $this->createLanguage('ka');
        $this->createLanguage('en', false);

        $this->seed(SiteDemoSeeder::class);

        $page = Page::query()->where('template', 'about')->sole();
        $this->assertTrue($page->published);
        $this->assertSame(SitePageTemplateSeeder::ABOUT_BLOCKS, $page->block_types);

        $ka = $page->translations()->where('locale', 'ka')->sole();
        $en = $page->translations()->where('locale', 'en')->sole();
        $this->assertSame('about', $ka->slug);
        $this->assertSame('about-us', $en->slug);
        $this->assertSame(SitePageTemplateSeeder::ABOUT_BLOCKS, $ka->blocks()->orderBy('sort_order')->pluck('type')->all());

        $why = $en->blocks()->where('type', 'about_why_choose_us')->sole()->data;
        $this->assertNotSame('', $why['title']);
        $this->assertNotEmpty($why['list_one'][0]['text']);
        Storage::disk('public')->assertExists($why['image']);

        $gallery = $ka->blocks()->where('type', 'about_gallery')->sole()->data;
        $this->assertCount(3, $gallery['images']);
    }

    public function test_demo_seeder_creates_every_site_page(): void
    {
        Storage::fake('public');
        $this->createLanguage('ka');
        $this->createLanguage('en', false);

        $this->seed(SiteDemoSeeder::class);

        $expected = ['about' => 'about-us', 'contact' => 'contact-us', 'faq' => 'faqs', 'gallery' => 'our-gallery',
            'history' => 'our-history', 'reservation' => 'book-a-table', 'menu' => 'food-menu', 'blog' => 'our-blog'];
        foreach ($expected as $template => $enSlug) {
            $page = Page::query()->where('template', $template)->sole();
            $this->assertSame($template, $page->translations()->where('locale', 'ka')->value('slug'));
            $this->assertSame($enSlug, $page->translations()->where('locale', 'en')->value('slug'));
        }

        $menu = Page::query()->where('template', 'menu')->sole();
        $this->assertSame(SitePageTemplateSeeder::TEMPLATES['menu']['blocks'], $menu->block_types);
        $this->assertSame(['menu_full', 'menu_special_banner'],
            $menu->translations()->where('locale', 'ka')->sole()->blocks()->orderBy('sort_order')->pluck('type')->all());

        $faq = Page::query()->where('template', 'faq')->sole()->translations()->where('locale', 'en')->sole()
            ->blocks()->sole()->data;
        $this->assertNotEmpty($faq['items'][0]['question']);

        $gallery = Page::query()->where('template', 'gallery')->sole()->translations()->where('locale', 'ka')->sole()
            ->blocks()->sole()->data;
        $this->assertCount(14, $gallery['images']);
        Storage::disk('public')->assertExists($gallery['images'][0]);
    }

    public function test_demo_seeder_creates_the_blog_posts_once(): void
    {
        Storage::fake('public');
        $this->createLanguage('ka');
        $this->createLanguage('en', false);

        $this->seed(SiteDemoSeeder::class);
        $this->seed(SiteDemoSeeder::class);

        $this->assertSame(3, Post::query()->count());
        foreach (['khinkali-history', 'spring-menu', 'visit-this-weekend'] as $slug) {
            $ka = PostTranslation::query()->where('slug', $slug)->sole();
            $this->assertSame('ka', $ka->locale);
            $en = $ka->post->translations()->where('locale', 'en')->sole();
            $this->assertSame("{$slug}-en", $en->slug);
            $this->assertTrue($ka->post->published);
            Storage::disk('public')->assertExists($ka->post->feature_image);

            $article = $en->blocks()->where('type', 'blog_article')->sole()->data;
            $this->assertNotEmpty($article['paragraphs'][0]['text']);
            $this->assertNotEmpty($article['tags']);
        }
    }

    public function test_demo_seeder_creates_the_header_menu_once(): void
    {
        Storage::fake('public');
        $this->createLanguage('ka');
        $this->createLanguage('en', false);

        $this->seed(SiteDemoSeeder::class);
        $this->seed(SiteDemoSeeder::class);

        $ka = $this->getJson('/api/web/navigation?locale=ka')->assertOk()->json('headerMenuItems');
        $en = $this->getJson('/api/web/navigation?locale=en')->assertOk()->json('headerMenuItems');
        $this->assertSame(
            ['/', '/menu', '/about', '/gallery', '/reservation', '/blog', '/history', '/faq', '/contact'],
            array_column($ka, 'url')
        );
        $this->assertSame('მთავარი', $ka[0]['label']);
        $this->assertSame('Home', $en[0]['label']);
    }

    public function test_page_api_returns_about_blocks_in_order_for_both_locales(): void
    {
        Storage::fake('public');
        $this->createLanguage('ka');
        $this->createLanguage('en', false);
        $this->seed(SiteDemoSeeder::class);

        $response = $this->getJson('/api/web/pages/about?locale=en')->assertOk();

        $this->assertSame('about', $response->json('page.template'));
        $this->assertSame(SitePageTemplateSeeder::ABOUT_BLOCKS, array_column($response->json('page.blocks'), 'type'));
        $this->assertIsArray($response->json('page.blocks.0.data.list_one'));
        $this->assertCount(3, $response->json('page.blocks.3.data.images'));
    }

    public function test_demo_seeder_does_not_overwrite_an_edited_page(): void
    {
        Storage::fake('public');
        $this->createLanguage('ka');
        $this->createLanguage('en', false);
        $this->seed(SiteDemoSeeder::class);
        PageTranslation::query()->where('slug', 'about')->update(['title' => 'ადმინის სათაური']);

        $this->seed(SiteDemoSeeder::class);

        $this->assertSame(1, Page::query()->where('template', 'about')->count());
        $this->assertSame('ადმინის სათაური', PageTranslation::query()->where('slug', 'about')->value('title'));
    }
}
