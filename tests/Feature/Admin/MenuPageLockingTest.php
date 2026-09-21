<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MenuPageLockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_menu_item_cannot_override_page_slug_or_label_from_menu_form(): void
    {
        $this->seedLanguages();
        $admin = $this->createAdmin();
        [$menu, $menuItem, $page] = $this->seedMenuAndPage();

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.menus.update', $menu), [
                'title' => 'Header',
                'slug' => 'header',
                'is_active' => 1,
                'items' => [
                    'item_1' => [
                        'id' => $menuItem->id,
                        'parent_key' => '',
                        'type' => 'page',
                        'reference_id' => $page->id,
                        'url' => '/hacked-url',
                        'target' => '_self',
                        'order' => 0,
                        'labels' => [
                            'en' => 'Hacked Label',
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseHas('page_translations', [
            'page_id' => $page->id,
            'locale' => 'en',
            'slug' => 'about-us',
            'title' => 'About us',
        ]);

        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'url' => '/about-us',
        ]);

        $this->assertDatabaseHas('menu_item_translations', [
            'menu_item_id' => $menuItem->id,
            'locale' => 'en',
            'label' => 'About us',
            'slug' => 'about-us',
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
    }

    private function createAdmin(): AdminUser
    {
        $role = AdminRole::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'All access',
        ]);

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0:Menu,1:MenuItem,2:Page}
     */
    private function seedMenuAndPage(): array
    {
        $page = Page::query()->create([
            'template' => 'default',
            'published' => true,
            'sort_order' => 1,
        ]);

        $page->translations()->create([
            'locale' => 'en',
            'title' => 'About us',
            'slug' => 'about-us',
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
            'reference_id' => $page->id,
            'url' => '/about-us',
            'target' => '_self',
        ]);

        $menuItem->translations()->create([
            'locale' => 'en',
            'label' => 'About us',
            'slug' => 'about-us',
        ]);

        return [$menu, $menuItem, $page];
    }
}
