<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Creates the site's blog posts from data/blog-posts.json (exported from the site).
 * Images are copied by SitePagesDemoSeeder. A post whose slug already exists is skipped.
 */
class BlogDemoSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{slug: array<string, string>, published_at: string, feature_image: string, translations: array<string, array{title: string, excerpt: string, category: string, article: array<string, mixed>}>}> $posts */
        $posts = json_decode(File::get(database_path('seeders/data/blog-posts.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($posts as $definition) {
            if (PostTranslation::query()->whereIn('slug', array_values($definition['slug']))->exists()) {
                continue;
            }

            DB::transaction(function () use ($definition): void {
                $post = Post::query()->create([
                    'category' => $definition['translations']['ka']['category'] ?? null,
                    'feature_image' => $definition['feature_image'],
                    'block_types' => ['blog_article'],
                    'sort_order' => 0,
                    'published' => true,
                    'published_at' => $definition['published_at'],
                ]);

                foreach ($definition['translations'] as $locale => $translation) {
                    $row = $post->translations()->create([
                        'locale' => $locale,
                        'title' => $translation['title'],
                        'slug' => $definition['slug'][$locale],
                        'excerpt' => $translation['excerpt'],
                    ]);
                    $row->blocks()->create([
                        'type' => 'blog_article',
                        'data' => $translation['article'],
                        'sort_order' => 10,
                    ]);
                }
            });
        }
    }
}
