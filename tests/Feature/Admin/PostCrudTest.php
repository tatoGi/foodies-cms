<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_posts_index(): void
    {
        $this->get(route('admin.posts.index'))->assertRedirect();
    }

    public function test_admin_can_view_posts_index(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.posts.index'))
            ->assertOk();
    }

    // ─── create / store ───────────────────────────────────────────────────────

    public function test_admin_can_view_create_post(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.posts.create'))
            ->assertOk();
    }

    public function test_admin_can_store_post(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.posts.store'), [
                'published' => true,
                'category' => 'services',
                'names' => ['en' => 'My First Post'],
                'slugs' => ['en' => 'my-first-post'],
            ])
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('posts', ['category' => 'services']);
        $this->assertDatabaseHas('post_translations', ['locale' => 'en', 'slug' => 'my-first-post']);
    }

    public function test_store_post_requires_at_least_one_name_and_slug(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.posts.store'), [
                'names' => ['en' => ''],
                'slugs' => ['en' => ''],
            ])
            ->assertSessionHasErrors('names');
    }

    public function test_store_post_requires_slug_when_name_given(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.posts.store'), [
                'names' => ['en' => 'Post Without Slug'],
                'slugs' => ['en' => ''],
            ])
            ->assertSessionHasErrors('slugs.en');
    }

    public function test_store_post_rejects_duplicate_slug(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $post = Post::query()->create(['sort_order' => 1, 'published' => true, 'block_types' => []]);
        PostTranslation::query()->create(['post_id' => $post->id, 'locale' => 'en', 'title' => 'Existing', 'slug' => 'existing-slug']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.posts.store'), [
                'names' => ['en' => 'New Post'],
                'slugs' => ['en' => 'existing-slug'],
            ])
            ->assertSessionHasErrors('slugs.en');
    }

    // ─── edit / update ────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_post(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $post = Post::query()->create(['sort_order' => 1, 'published' => true, 'block_types' => []]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.posts.edit', $post))
            ->assertOk();
    }

    public function test_admin_can_update_post(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $post = Post::query()->create(['sort_order' => 1, 'published' => false, 'block_types' => []]);
        PostTranslation::query()->create(['post_id' => $post->id, 'locale' => 'en', 'title' => 'Old Name', 'slug' => 'old-post-slug']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.posts.update', $post), [
                'published' => true,
                'names' => ['en' => 'Updated Post'],
                'slugs' => ['en' => 'updated-post-slug'],
            ])
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('post_translations', ['post_id' => $post->id, 'slug' => 'updated-post-slug']);
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_admin_can_delete_post(): void
    {
        $admin = $this->createAdminUser();

        $post = Post::query()->create(['sort_order' => 1, 'published' => true, 'block_types' => []]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    // ─── reorder ──────────────────────────────────────────────────────────────

    public function test_admin_can_reorder_posts(): void
    {
        $admin = $this->createAdminUser();

        $p1 = Post::query()->create(['sort_order' => 1, 'published' => true, 'block_types' => []]);
        $p2 = Post::query()->create(['sort_order' => 2, 'published' => true, 'block_types' => []]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.posts.reorder'), [
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
            ->postJson(route('admin.posts.reorder'), [])
            ->assertStatus(422);
    }
}
