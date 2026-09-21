<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Media;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\WebsiteMenuRepositoryInterface;
use App\Repositories\Contracts\WebsitePostRepositoryInterface;
use App\Support\FrontendBlockTypeMapper;
use App\Traits\ResolvesTranslationBlocks;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebsitePostService
{
    use ResolvesTranslationBlocks;

    public function __construct(
        private readonly WebsitePostRepositoryInterface $postRepo,
        private readonly WebsiteMenuRepositoryInterface $menuRepo,
        private readonly LanguageRepositoryInterface $langRepo,
    ) {}

    private function toAssetUrl(mixed $path): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    public function buildPostData(string $slug, string $locale): ?array
    {
        $defaultLocale = strtolower($this->langRepo->defaultLocale());
        $translation = $this->postRepo->findPublishedBySlug($slug);

        if ($translation === null) {
            return null;
        }

        $post = $translation->post;
        $resolvedTranslation = $this->resolveTranslation($post, $locale, $defaultLocale);

        // All localized blocks with cross-locale fallback (same logic as PageService)
        $blocks = $this->resolveLocalizedBlocks($post, $locale, $defaultLocale)
            ->sortBy('sort_order')
            ->map(fn ($block) => [
                'type' => FrontendBlockTypeMapper::toFrontend((string) $block->type),
                'sort_order' => (int) ($block->sort_order ?? 0),
                'data' => (array) ($block->data ?? []),
            ])
            ->values()
            ->all();

        $relatedPosts = $this->postRepo->relatedPublished(
            (int) $post->id,
            (string) ($post->category ?? ''),
            $locale,
            $defaultLocale
        );

        // Collect image paths for media alt lookup
        $imagePaths = [];
        foreach ($blocks as $block) {
            $this->gatherPaths((array) ($block['data'] ?? []), $imagePaths);
        }
        if ($post->feature_image) {
            $imagePaths[] = trim((string) $post->feature_image);
        }
        $mediaAlts = $this->fetchMediaAlts(array_unique(array_filter($imagePaths)), $locale);

        $introBlock = collect($blocks)->firstWhere('type', 'post_intro');
        $excerpt = data_get($introBlock, 'data.post_text')
            ?: $resolvedTranslation?->excerpt
            ?: Str::limit(strip_tags((string) ($resolvedTranslation?->content ?? '')), 200);

        return [
            'post' => [
                'id' => (int) $post->id,
                'slug' => (string) ($resolvedTranslation?->slug ?? $translation->slug),
                'title' => (string) ($resolvedTranslation?->title ?: config('app.name')),
                'content' => (string) ($resolvedTranslation?->content ?? ''),
                'excerpt' => (string) $excerpt,
                'category' => (string) ($post->category ?? ''),
                'published_at' => optional($post->published_at ?? $post->created_at)->format('d/m/Y'),
                'feature_image' => $this->toAssetUrl($post->feature_image),
                'blocks' => $blocks,
            ],
            'relations' => [
                'posts' => $relatedPosts,
            ],
            'seo' => [
                'meta_title' => $resolvedTranslation?->meta_title,
                'meta_description' => $resolvedTranslation?->meta_description,
                'keywords' => $resolvedTranslation?->keywords,
                'canonical_url' => $resolvedTranslation?->canonical_url,
                'og_image' => $this->toAssetUrl($post->feature_image),
            ],
            'media_alts' => $mediaAlts,
        ];
    }

    private function gatherPaths(mixed $data, array &$paths): void
    {
        if (is_string($data) && $data !== '' && preg_match('/\.(jpg|jpeg|png|gif|webp|svg|avif)$/i', $data)) {
            $paths[] = $data;
        } elseif (is_array($data)) {
            foreach ($data as $v) {
                $this->gatherPaths($v, $paths);
            }
        }
    }

    private function fetchMediaAlts(array $paths, string $locale): array
    {
        if (empty($paths)) {
            return [];
        }

        $result = [];
        Media::whereIn('path', $paths)->get()->each(function ($media) use (&$result, $locale): void {
            $alt = $media->localizedField('alt_text', $locale);
            if ($alt !== null && $alt !== '') {
                $result[(string) $media->path] = $alt;
            }
        });

        return $result;
    }
}
