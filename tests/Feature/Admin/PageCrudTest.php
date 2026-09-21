<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageCrudTest extends TestCase
{
    use RefreshDatabase;

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_pages_index(): void
    {
        $this->get(route('admin.pages.index'))->assertRedirect();
    }

    public function test_admin_can_view_pages_index(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.pages.index'))
            ->assertOk();
    }

    // ─── create / store ───────────────────────────────────────────────────────

    public function test_admin_can_view_create_page(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.pages.create'))
            ->assertOk();
    }

    public function test_admin_can_store_page(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');
        $template = $this->createPageTemplate('default');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.pages.store'), [
                'template' => $template->slug,
                'published' => true,
                'names' => ['en' => 'About Us'],
                'slugs' => ['en' => 'about-us'],
            ])
            ->assertRedirect(route('admin.pages.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', ['template' => 'default']);
        $this->assertDatabaseHas('page_translations', ['locale' => 'en', 'slug' => 'about-us']);
    }

    public function test_store_page_requires_at_least_one_name(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');
        $template = $this->createPageTemplate('default');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.pages.store'), [
                'template' => $template->slug,
                'names' => ['en' => ''],
                'slugs' => ['en' => ''],
            ])
            ->assertSessionHasErrors('names');
    }

    public function test_store_page_rejects_duplicate_slug(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');
        $template = $this->createPageTemplate('default');

        // First page
        $page = Page::query()->create(['template' => 'default', 'sort_order' => 1, 'published' => true]);
        PageTranslation::query()->create(['page_id' => $page->id, 'locale' => 'en', 'title' => 'About', 'slug' => 'about-us']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.pages.store'), [
                'template' => $template->slug,
                'names' => ['en' => 'About Again'],
                'slugs' => ['en' => 'about-us'],
            ])
            ->assertSessionHasErrors();
    }

    // ─── edit / update ────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_page(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');
        $this->createPageTemplate('default');

        $page = Page::query()->create(['template' => 'default', 'sort_order' => 1, 'published' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.pages.edit', $page))
            ->assertOk();
    }

    public function test_admin_can_update_page(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');
        $template = $this->createPageTemplate('default');

        $page = Page::query()->create(['template' => 'default', 'sort_order' => 1, 'published' => false]);
        PageTranslation::query()->create(['page_id' => $page->id, 'locale' => 'en', 'title' => 'Old', 'slug' => 'old-slug']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.pages.update', $page), [
                'template' => $template->slug,
                'published' => true,
                'names' => ['en' => 'New Title'],
                'slugs' => ['en' => 'new-slug'],
            ])
            ->assertRedirect(route('admin.pages.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('page_translations', ['page_id' => $page->id, 'slug' => 'new-slug']);
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_admin_can_delete_page(): void
    {
        $admin = $this->createAdminUser();
        $this->createPageTemplate('default');

        $page = Page::query()->create(['template' => 'default', 'sort_order' => 1, 'published' => true]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    }

    // ─── reorder ──────────────────────────────────────────────────────────────

    public function test_admin_can_reorder_pages(): void
    {
        $admin = $this->createAdminUser();
        $this->createPageTemplate('default');

        $p1 = Page::query()->create(['template' => 'default', 'sort_order' => 1, 'published' => true]);
        $p2 = Page::query()->create(['template' => 'default', 'sort_order' => 2, 'published' => true]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.pages.reorder'), [
                'ordered_ids' => [$p2->id, $p1->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // reorderByIds uses 0-based index positions
        $this->assertEquals(0, $p2->fresh()->sort_order);
        $this->assertEquals(1, $p1->fresh()->sort_order);
    }

    public function test_reorder_returns_422_when_no_ids_provided(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.pages.reorder'), [])
            ->assertStatus(422);
    }
}
