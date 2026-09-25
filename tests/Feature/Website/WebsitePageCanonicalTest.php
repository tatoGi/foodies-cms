<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\Page;
use App\Models\PageSlugAlias;
use App\Models\PageTranslation;
use App\Services\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsitePageCanonicalTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_slug_redirects_to_the_canonical_page_slug(): void
    {
        $this->createLanguage('ka');

        $page = Page::query()->create([
            'template' => 'inner',
            'published' => true,
            'block_types' => [],
        ]);
        PageTranslation::query()->create([
            'page_id' => $page->id,
            'locale' => 'ka',
            'title' => 'ჩვენს შესახებ',
            'slug' => 'about-us',
            'meta_title' => 'ჩვენს შესახებ SEO',
            'meta_description' => 'საცხობის ისტორია',
        ]);
        PageSlugAlias::query()->create([
            'page_id' => $page->id,
            'locale' => 'ka',
            'slug' => 'about',
        ]);

        $this->getJson('/api/web/pages/about?locale=ka')
            ->assertOk()
            ->assertJsonPath('redirect.slug', 'about-us');

        $this->getJson('/api/web/pages/about-us?locale=ka')
            ->assertOk()
            ->assertJsonPath('page.slug', 'about-us')
            ->assertJsonPath('page.template', 'inner')
            ->assertJsonPath('seo.meta_title', 'ჩვენს შესახებ SEO')
            ->assertJsonPath('seo.locales.ka', 'about-us');

        $this->getJson('/api/web/pages')
            ->assertOk()
            ->assertJsonPath('pages.0.slugs.ka', 'about-us')
            ->assertJsonPath('pages.0.is_home', false);
    }

    public function test_changing_a_page_slug_keeps_the_previous_slug_as_an_alias(): void
    {
        $page = Page::query()->create([
            'template' => 'inner',
            'published' => true,
            'block_types' => [],
        ]);

        app(PageService::class)->rememberPreviousSlug($page, 'ka', 'old-story', 'new-story');

        $this->assertDatabaseHas('page_slug_aliases', [
            'page_id' => $page->id,
            'locale' => 'ka',
            'slug' => 'old-story',
        ]);
    }
}
