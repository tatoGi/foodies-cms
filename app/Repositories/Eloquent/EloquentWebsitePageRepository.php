<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Repositories\Contracts\WebsitePageRepositoryInterface;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentWebsitePageRepository implements WebsitePageRepositoryInterface
{
    public function findPublishedBySlug(string $slug): ?PageTranslation
    {
        return PageTranslation::query()
            ->where('slug', trim($slug))
            ->whereHas('page', static fn ($q) => $q->where('published', true))
            ->with([
                'page.translations.blocks',
                'page.posts.translations',
                'page.products.translations.blocks',
                'page.parent.translations',
                'page.children' => static fn ($q) => $q->where('published', true)->orderBy('sort_order')->orderBy('id'),
                'page.children.translations',
                'page.children.products.translations.blocks',
            ])
            ->first();
    }

    /**
     * @return array<int, array{id:int,slug:string,title:string,template:string,url:string,api_url:string}>
     */
    public function publishedPageSlugs(string $locale, string $fallbackLocale): array
    {
        if (! $this->cmsTablesAvailable()) {
            return [];
        }

        return Page::query()
            ->with('translations')
            ->where('published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (Page $page) use ($locale, $fallbackLocale): ?array {
                $translation = $page->translations->firstWhere('locale', $locale)
                    ?? $page->translations->firstWhere('locale', $fallbackLocale)
                    ?? $page->translations->first();

                if ($translation === null) {
                    return null;
                }

                $slug = trim((string) $translation->slug);
                if ($slug === '') {
                    return null;
                }

                return [
                    'id' => (int) $page->id,
                    'slug' => $slug,
                    'is_home' => (bool) $page->is_home,
                    'title' => trim((string) $translation->title) !== ''
                        ? (string) $translation->title
                        : '#'.$page->id,
                    'template' => (string) ($page->template ?: 'default'),
                    'url' => $page->is_home ? '/' : '/'.$slug,
                    'api_url' => $page->is_home ? '/api/web/home' : '/api/web/pages/'.$slug,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function findHomepage(string $locale, string $fallbackLocale): ?Page
    {
        if (! $this->cmsTablesAvailable()) {
            return null;
        }

        $priority = ['home', 'homepage', 'main', 'index'];

        $page = Page::query()
            ->with([
                'translations.blocks',
                'parent.translations',
                'children.translations',
                'posts.translations.blocks',
                'products.translations.blocks',
            ])
            ->where('is_home', true)
            ->orderBy('id')
            ->first();

        if ($page instanceof Page) {
            return $page;
        }

        $page = Page::query()
            ->with([
                'translations.blocks',
                'parent.translations',
                'children.translations',
                'posts.translations.blocks',
                'products.translations.blocks',
            ])
            ->whereIn('template', $priority)
            ->orderBy('id')
            ->get()
            ->sortBy(static function (Page $item) use ($priority): int {
                $rank = array_search((string) $item->template, $priority, true);

                return $rank === false ? count($priority) : $rank;
            })
            ->first();

        if ($page instanceof Page) {
            return $page;
        }

        return Page::query()
            ->with([
                'translations.blocks',
                'parent.translations',
                'children.translations',
                'posts.translations.blocks',
                'products.translations.blocks',
            ])
            ->orderBy('id')
            ->get()
            ->first(function (Page $item) use ($locale, $fallbackLocale): bool {
                foreach ($item->translations as $translation) {
                    if (! in_array((string) $translation->locale, [$locale, $fallbackLocale], true)) {
                        continue;
                    }
                    $slug = mb_strtolower(trim((string) $translation->slug));
                    if (in_array($slug, ['home', 'homepage', 'main', 'index'], true)) {
                        return true;
                    }
                }

                return false;
            });
    }

    public function findPublishedByTemplate(string $template, string $locale, string $fallbackLocale): ?Page
    {
        if (! $this->cmsTablesAvailable()) {
            return null;
        }

        $normalizedTemplate = trim($template);
        if ($normalizedTemplate === '') {
            return null;
        }

        return Page::query()
            ->with([
                'translations.blocks',
                'parent.translations',
                'children.translations',
                'posts.translations.blocks',
                'products.translations.blocks',
            ])
            ->where('published', true)
            ->where('template', $normalizedTemplate)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<int, array{slug:string,title:string,excerpt:string,published_at:string|null}>
     */
    public function recentPublishedPosts(string $locale, string $fallbackLocale, int $limit = 3): array
    {
        if (! Schema::hasTable('posts')) {
            return [];
        }

        return Post::query()
            ->with('translations')
            ->where('published', true)
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

                return [
                    'slug' => (string) $translation->slug,
                    'title' => (string) $translation->title,
                    'excerpt' => Str::limit(strip_tags((string) $translation->content), 180),
                    'published_at' => optional($post->published_at ?? $post->created_at)->format('d/m/Y'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function cmsTablesAvailable(): bool
    {
        return Schema::hasTable('pages')
            && Schema::hasTable('page_translations')
            && Schema::hasTable('page_content_blocks');
    }
}
