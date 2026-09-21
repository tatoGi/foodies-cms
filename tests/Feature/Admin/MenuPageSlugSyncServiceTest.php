<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\MenuPageSlugSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPageSlugSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_from_page_overwrites_menu_page_item_url_and_slug(): void
    {
        $this->seedLanguages();
        [$page, $menuItem] = $this->seedPageAndMenuItem();

        $menuItem->update(['url' => '/hacked-url']);
        $menuItem->translations()->updateOrCreate(
            ['locale' => 'en'],
            ['label' => 'Hacked Label', 'slug' => 'hacked-label']
        );

        /** @var MenuPageSlugSyncService $service */
        $service = app(MenuPageSlugSyncService::class);
        $service->syncMenuItemsFromPage((int) $page->id);

        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'url' => '/about-us',
        ]);

        $this->assertDatabaseHas('menu_item_translations', [
            'menu_item_id' => $menuItem->id,
            'locale' => 'en',
            'slug' => 'about-us',
        ]);
    }

    public function test_changing_page_slug_updates_navigation_menu_item_slug_and_url(): void
    {
        $this->seedLanguages();
        [$page, $menuItem] = $this->seedPageAndMenuItem();

        $page->translations()->where('locale', 'en')->update(['slug' => 'about-company']);

        /** @var MenuPageSlugSyncService $service */
        $service = app(MenuPageSlugSyncService::class);
        $service->syncMenuItemsFromPage((int) $page->id);

        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'url' => '/about-company',
        ]);

        $this->assertDatabaseHas('menu_item_translations', [
            'menu_item_id' => $menuItem->id,
            'locale' => 'en',
            'slug' => 'about-company',
        ]);
    }

    private function seedLanguages(): void
    {
        Language::query()->create([
            'code' => 'en',
            'name' => 'English',
            'english_name' => 'English',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        Language::query()->create([
            'code' => 'ka',
            'name' => '???????',
            'english_name' => 'Georgian',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 2,
        ]);
    }

    /**
     * @return array{0:Page,1:MenuItem}
     */
    private function seedPageAndMenuItem(): array
    {
        $page = Page::query()->create([
            'template' => 'default',
            'sort_order' => 1,
            'published' => true,
        ]);

        $page->translations()->create([
            'locale' => 'en',
            'title' => 'About',
            'slug' => 'about-us',
        ]);

        $page->translations()->create([
            'locale' => 'ka',
            'title' => '???? ???????',
            'slug' => 'chven-shesaxeb',
        ]);

        $menu = Menu::query()->create([
            'title' => 'Header',
            'slug' => 'header',
            'is_active' => true,
        ]);

        $menuItem = MenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'is_active' => true,
            'order' => 0,
            'type' => 'page',
            'url' => '/about-us',
            'target' => '_self',
            'reference_id' => $page->id,
        ]);

        $menuItem->translations()->create([
            'locale' => 'en',
            'label' => 'About',
            'slug' => 'about',
        ]);

        return [$page, $menuItem];
    }
}
