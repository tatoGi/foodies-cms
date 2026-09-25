<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\BlockTypeDefinition;
use App\Models\Post;
use App\Models\PostTranslation;
use Database\Seeders\SitePageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsitePostListApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_list_returns_published_posts_newest_first_in_the_requested_language(): void
    {
        $this->createLanguage('ka');
        $this->createLanguage('en', false);
        $this->makePost('khinkali', 'ხინკალი', 'Khinkali', '2026-03-03', true);
        $this->makePost('spring', 'გაზაფხული', 'Spring', '2026-04-14', true);
        $this->makePost('draft', 'მონახაზი', 'Draft', '2026-05-01', false);

        $response = $this->getJson('/api/web/posts?locale=en&limit=5')->assertOk();

        $this->assertSame(['spring-en', 'khinkali-en'], array_column($response->json('posts'), 'slug'));
        $this->assertSame('Spring', $response->json('posts.0.title'));
        $this->assertSame('2026-04-14', $response->json('posts.0.published_at'));
        $this->assertStringContainsString('demo/spring.jpg', (string) $response->json('posts.0.feature_image'));

        $this->assertCount(1, $this->getJson('/api/web/posts?locale=ka&limit=1')->json('posts'));
    }

    public function test_template_seeder_creates_the_blog_article_post_block(): void
    {
        $this->seed(SitePageTemplateSeeder::class);

        $block = BlockTypeDefinition::query()->where('key', 'blog_article')->sole();
        $this->assertSame('post', $block->scope);
        $paragraphs = collect($block->schema['fields'])->firstWhere('key', 'paragraphs');
        $this->assertSame('repeater', $paragraphs['type']);
    }

    public function test_saving_a_post_refreshes_the_site_cache(): void
    {
        config([
            'services.frontend.revalidate_url' => 'http://frontend.test/api/revalidate',
            'services.frontend.revalidate_secret' => 'test-secret',
        ]);
        Http::fake(['http://frontend.test/*' => Http::response(['ok' => true])]);
        $this->createLanguage('ka');
        $post = $this->makePost('khinkali', 'ხინკალი', 'Khinkali', '2026-03-03', true);

        $this->actingAs($this->createAdminUser(), 'admin')
            ->put(route('admin.posts.update', $post), [
                'published' => 1,
                'names' => ['ka' => 'ახალი სათაური'],
                'slugs' => ['ka' => 'khinkali'],
            ])
            ->assertRedirect();

        Http::assertSent(function (Request $request): bool {
            $tags = $request->data()['tags'] ?? [];

            return $request->url() === 'http://frontend.test/api/revalidate'
                && in_array('posts', $tags, true)
                && in_array('pages', $tags, true)
                && in_array('post:khinkali', $tags, true);
        });
    }

    private function makePost(string $slug, string $ka, string $en, string $date, bool $published): Post
    {
        $post = Post::query()->create([
            'feature_image' => "demo/{$slug}.jpg",
            'published' => $published,
            'published_at' => $date,
            'block_types' => [],
            'sort_order' => 0,
        ]);
        PostTranslation::query()->create(['post_id' => $post->id, 'locale' => 'ka', 'title' => $ka, 'slug' => $slug]);
        PostTranslation::query()->create(['post_id' => $post->id, 'locale' => 'en', 'title' => $en, 'slug' => "{$slug}-en"]);

        return $post;
    }
}
