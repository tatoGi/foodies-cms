<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteNavigationAndPageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_payload_contains_page_slug_and_api_url(): void
    {
        $this->seedLanguages();
        $page = $this->seedPublishedPage();
        $this->seedHeaderMenuForPage($page);

        $response = $this->getJson('/api/web/navigation?locale=en');

        $response->assertOk()
            ->assertJsonPath('locale', 'en')
            ->assertJsonStructure([
                'headerLogo',
                'footerLogo',
                'footerContactText',
                'footerContactByLocale',
            ])
            ->assertJsonPath('headerMenuItems.0.type', 'page')
            ->assertJsonPath('headerMenuItems.0.slug', 'about-us')
            ->assertJsonPath('headerMenuItems.0.url', '/about-us')
            ->assertJsonPath('headerMenuItems.0.api_url', '/api/web/pages/about-us')
            ->assertJsonPath('pages.0.slug', 'about-us')
            ->assertJsonPath('pages.0.api_url', '/api/web/pages/about-us');
    }

    public function test_page_index_returns_published_page_slugs(): void
    {
        $this->seedLanguages();
        $this->seedPublishedPage();

        $response = $this->getJson('/api/web/pages?locale=en');

        $response->assertOk()
            ->assertJsonPath('locale', 'en')
            ->assertJsonPath('pages.0.slug', 'about-us')
            ->assertJsonPath('pages.0.url', '/about-us')
            ->assertJsonPath('pages.0.api_url', '/api/web/pages/about-us');
    }

    public function test_page_show_uses_requested_locale_for_content_resolution(): void
    {
        $this->seedLanguages();
        $this->seedPublishedPage();

        $response = $this->getJson('/api/web/pages/about-us?locale=ka');

        $response->assertOk()
            ->assertJsonPath('locale', 'ka')
            ->assertJsonPath('page.slug', 'chven-shesaxeb')
            ->assertJsonPath('page.title', 'ჩვენ შესახებ');
    }

    private function seedLanguages(): void
    {
        Language::query()->create([
            'code' => 'en',
            'country_code' => 'US',
            'name' => 'English',
            'english_name' => 'English',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        Language::query()->create([
            'code' => 'ka',
            'country_code' => 'GE',
            'name' => 'ქართული',
            'english_name' => 'Georgian',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 2,
        ]);
    }

    private function seedPublishedPage(): Page
    {
        $page = Page::query()->create([
            'parent_id' => null,
            'template' => 'default',
            'block_types' => [],
            'sort_order' => 1,
            'published' => true,
            'show_in_menu' => true,
            'is_home' => false,
        ]);

        $page->translations()->create([
            'locale' => 'en',
            'title' => 'About us',
            'slug' => 'about-us',
            'description' => 'About page in English',
        ]);

        $page->translations()->create([
            'locale' => 'ka',
            'title' => 'ჩვენ შესახებ',
            'slug' => 'chven-shesaxeb',
            'description' => 'About page in Georgian',
        ]);

        return $page;
    }

    private function seedHeaderMenuForPage(Page $page): void
    {
        $menu = Menu::query()->create([
            'title' => 'Header menu',
            'slug' => 'header',
            'is_active' => true,
        ]);

        $item = MenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'is_active' => true,
            'order' => 0,
            'type' => 'page',
            'url' => null,
            'target' => '_self',
            'reference_id' => $page->id,
        ]);

        $item->translations()->create([
            'locale' => 'en',
            'label' => 'About',
            'slug' => 'about',
        ]);
    }
}
