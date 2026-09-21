<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Post;
use App\Models\PostSlugAlias;
use App\Models\PostTranslation;
use App\Repositories\Contracts\WebsitePostRepositoryInterface;
use Illuminate\Support\Str;

class EloquentWebsitePostRepository implements WebsitePostRepositoryInterface
{
    public function findPublishedBySlug(string $slug): ?PostTranslation
    {
        $candidates = collect([
            trim($slug),
            trim(rawurldecode($slug)),
            trim(urldecode($slug)),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $translation = PostTranslation::query()
            ->whereIn('slug', $candidates)
            ->whereHas('post', static fn ($q) => $q->where('published', true))
            ->with(['post.translations.blocks'])
            ->first();

        if ($translation instanceof PostTranslation) {
            return $translation;
        }

        $alias = PostSlugAlias::query()
            ->whereIn('slug', $candidates)
            ->whereHas('post', static fn ($q) => $q->where('published', true))
            ->with(['post.translations.blocks'])
            ->first();

        if ($alias === null) {
            return null;
        }

        return $alias->post->translations->firstWhere('locale', $alias->locale)
            ?? $alias->post->translations->first();
    }

    /**
     * @return array<int, array{slug:string,title:string,excerpt:string}>
     */
    public function relatedPublished(
        int $postId,
        string $category,
        string $locale,
        string $fallbackLocale,
        int $limit = 3
    ): array {
        return Post::query()
            ->with(['translations.blocks'])
            ->where('published', true)
            ->where('id', '!=', $postId)
            ->where('category', $category)
            ->latest()
            ->take($limit)
            ->get()
            ->map(function (Post $post) use ($locale, $fallbackLocale): ?array {
                $translation = $post->translations->firstWhere('locale', $locale)
                    ?? $post->translations->firstWhere('locale', $fallbackLocale)
                    ?? $post->translations->first();

                if ($translation === null || trim((string) $translation->slug) === '') {
                    return null;
                }

                $blocks = $translation->blocks->map(fn ($b) => [
                    'type' => $b->type,
                    'data' => $b->data,
                ])->values()->all();

                $introBlock = collect($blocks)->firstWhere('type', 'post_intro');
                $excerpt = $introBlock['data']['post_text'] ?? null
                    ?? Str::limit(strip_tags((string) $translation->content), 140);

                return [
                    'id' => (int) $post->id,
                    'slug' => (string) $translation->slug,
                    'title' => (string) $translation->title,
                    'excerpt' => (string) $excerpt,
                    'feature_image' => $post->feature_image ?? $introBlock['data']['post_image'] ?? null,
                    'category' => (string) ($post->category ?? ''),
                    'published_at' => optional($post->published_at ?? $post->created_at)->format('d/m/Y'),
                    'blocks' => $blocks,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
