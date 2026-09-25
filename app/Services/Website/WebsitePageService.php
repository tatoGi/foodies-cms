<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageSlugAlias;
use App\Models\Product;
use App\Models\Reel;
use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\WebsiteMenuRepositoryInterface;
use App\Repositories\Contracts\WebsitePageRepositoryInterface;
use App\Support\FrontendBlockTypeMapper;
use App\Traits\ResolvesTranslationBlocks;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebsitePageService
{
    use ResolvesTranslationBlocks;

    public function __construct(
        private readonly WebsitePageRepositoryInterface $pageRepo,
        private readonly WebsiteMenuRepositoryInterface $menuRepo,
        private readonly LanguageRepositoryInterface $langRepo,
        private readonly BlockTypeRepositoryInterface $blockTypeRepository,
    ) {}

    /**
     * @return array{pages: array<int, array{is_home: bool, updated_at: string|null, slugs: array<string, string>}>}
     */
    public function publishedIndex(): array
    {
        $pages = Page::query()
            ->where('published', true)
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static function (Page $page): array {
                $slugs = [];
                foreach ($page->translations as $translation) {
                    $slug = trim((string) $translation->slug);
                    if ($slug !== '') {
                        $slugs[(string) $translation->locale] = $slug;
                    }
                }

                return [
                    'is_home' => (bool) $page->is_home,
                    'updated_at' => $page->updated_at?->toAtomString(),
                    'slugs' => $slugs,
                ];
            })
            ->values()
            ->all();

        return ['pages' => $pages];
    }

    public function buildPageData(string $slug, ?string $requestedLocale = null): ?array
    {
        $activeLanguages = $this->langRepo->getActiveLocales();
        $defaultLocale = strtolower($this->langRepo->defaultLocale());
        $locale = strtolower(trim((string) $requestedLocale)) ?: $defaultLocale;

        $normalizedSlug = trim($slug, " \t\n\r\0\x0B/");
        $homepage = $this->pageRepo->findHomepage($locale, $defaultLocale);
        $homepageSlugs = $homepage?->translations
            ?->pluck('slug')
            ->filter()
            ->map(static fn ($value): string => trim((string) $value))
            ->values()
            ->all() ?? [];

        $translation = in_array($normalizedSlug, ['home', ...$homepageSlugs], true)
            ? ($homepage?->translations->firstWhere('locale', $locale)
                ?? $homepage?->translations->firstWhere('locale', $defaultLocale)
                ?? $homepage?->translations->first())
            : $this->pageRepo->findPublishedBySlug($normalizedSlug);

        if ($translation === null) {
            return $this->redirectForAlias($normalizedSlug, $locale, $defaultLocale);
        }

        $page = $translation->page;
        $resolvedTranslation = $this->resolveTranslation($page, $locale, $defaultLocale);

        $blockDefinitions = $this->blockTypeRepository
            ->getEnabledForScope('page')
            ->keyBy('key');

        $blocks = $this->resolveLocalizedBlocks($page, $locale, $defaultLocale)
            ->sortBy('sort_order')
            ->map(function ($block) use ($blockDefinitions): array {
                $definition = $blockDefinitions->get((string) $block->type);
                $labels = (array) data_get($definition?->schema, 'translations.labels', []);
                $descriptions = (array) data_get($definition?->schema, 'translations.descriptions', []);

                return [
                    'type' => FrontendBlockTypeMapper::toFrontend((string) $block->type),
                    'label' => $labels[app()->getLocale()] ?? collect($labels)->first() ?? $definition?->label,
                    'description' => $descriptions[app()->getLocale()] ?? collect($descriptions)->first() ?? $definition?->description,
                    'sort_order' => (int) ($block->sort_order ?? 0),
                    'data' => (array) ($block->data ?? []),
                ];
            })
            ->values()
            ->all();

        $postCategory = match ($page->template) {
            'services' => 'service',
            'projects' => 'project',
            default => null,
        };

        $relatedPosts = $this->resolveRelatedPosts($page, $locale, $defaultLocale, $postCategory);

        $productTemplates = ['product', 'products', 'product-listing'];
        $isProductListingPage = in_array($page->template, $productTemplates, true);

        $childCategories = [];
        $productPool = $page->products;

        if ($isProductListingPage) {
            $productChildren = $page->children
                ->filter(static fn ($child) => in_array($child->template, $productTemplates, true));

            foreach ($productChildren as $child) {
                $childTranslation = $this->resolveTranslation($child, $locale, $defaultLocale);
                $childSlug = trim((string) ($childTranslation?->slug ?? ''));
                if ($childSlug === '') {
                    continue;
                }
                $activeChildProducts = $child->products
                    ->where('published', true)
                    ->where('is_active', true);
                $childCategories[] = [
                    'id' => (int) $child->id,
                    'slug' => $childSlug,
                    'title' => (string) ($childTranslation?->title ?? ''),
                    'feature_image' => $this->toAssetUrl($child->feature_image),
                    'product_count' => $activeChildProducts->count(),
                ];
                $productPool = $productPool->concat($activeChildProducts->values());
            }
        }

        $linkedProducts = $productPool
            ->where('published', true)
            ->where('is_active', true)
            ->unique('id')
            ->sortBy([
                ['is_featured', 'desc'],
                ['id', 'desc'],
            ])
            ->values()
            ->map(fn ($product) => $this->formatProduct($product, $locale, $defaultLocale))
            ->filter()
            ->values()
            ->all();

        $isHomepage = $slug === 'home' || $page->template === 'home' || (bool) $page->is_home;

        if ($isHomepage) {
            $linkedIds = collect($linkedProducts)->pluck('id')->all();
            $featuredProducts = Product::query()
                ->where('published', true)
                ->where('is_active', true)
                ->where('is_featured', true)
                ->whereNotIn('id', $linkedIds)
                ->with('translations.blocks')
                ->get()
                ->map(fn ($p) => $this->formatProduct($p, $locale, $defaultLocale))
                ->filter()
                ->values()
                ->all();
            $linkedProducts = array_merge($linkedProducts, $featuredProducts);
        }

        $reels = [];
        $projectSection = null;
        $blogSection = null;

        if ($isHomepage) {
            $reels = Reel::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($r) => $this->formatReel($r, $locale, $defaultLocale))
                ->filter()
                ->values()
                ->all();

            // Products flagged "Show in Reels" appear alongside regular reels.
            $productReels = Product::query()
                ->where('show_in_reels', true)
                ->where('published', true)
                ->where('is_active', true)
                ->with('translations.blocks')
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($p) => $this->formatProductReel($p, $locale, $defaultLocale))
                ->filter()
                ->values()
                ->all();

            $reels = array_merge($reels, $productReels);

            $projectSection = $this->resolveProjectSection($locale, $defaultLocale);
            $blogSection = $this->resolveBlogSection($locale, $defaultLocale);
        }

        // Collect all image paths across this page for media alt lookup
        $imagePaths = $this->collectImagePathsFromBlocks($blocks);
        if ($page->feature_image) {
            $imagePaths[] = trim((string) $page->feature_image);
        }
        foreach ($relatedPosts as $post) {
            if (! empty($post['feature_image'])) {
                $imagePaths[] = $this->rawPathFromUrl((string) $post['feature_image']);
            }
        }
        foreach ($linkedProducts as $product) {
            if (! empty($product['feature_image'])) {
                $imagePaths[] = $this->rawPathFromUrl((string) $product['feature_image']);
            }
        }
        $mediaAlts = $this->fetchMediaAlts(array_unique(array_filter($imagePaths)), $locale);

        return [
            'page' => [
                'id' => (int) $page->id,
                'is_home' => (bool) $page->is_home,
                'slug' => (string) ($resolvedTranslation?->slug ?? $slug),
                'title' => (string) ($resolvedTranslation?->title ?: config('app.name')),
                'description' => (string) ($resolvedTranslation?->description ?? ''),
                'template' => (string) ($page->template ?: 'default'),
                'feature_image' => $this->toAssetUrl($page->feature_image),
                'blocks' => $blocks,
            ],
            'relations' => [
                'posts' => $relatedPosts,
                'products' => $linkedProducts,
                'reels' => $reels,
                'categories' => $childCategories,
            ],
            'project_section' => $projectSection,
            'blog_section' => $blogSection,
            'seo' => [
                'meta_title' => $resolvedTranslation?->meta_title,
                'meta_description' => $resolvedTranslation?->meta_description,
                'keywords' => $resolvedTranslation?->keywords,
                'canonical_url' => $resolvedTranslation?->canonical_url,
                'og_image' => $this->toAssetUrl($page->feature_image),
                'locales' => $page->translations
                    ->mapWithKeys(static function ($translation): array {
                        $slug = trim((string) ($translation->slug ?? ''));

                        return $slug === '' ? [] : [(string) $translation->locale => $slug];
                    })
                    ->all(),
            ],
            'media_alts' => $mediaAlts,
        ];
    }

    /**
     * @return array{redirect: array{slug: string}}|null
     */
    private function redirectForAlias(string $slug, string $locale, string $defaultLocale): ?array
    {
        $alias = PageSlugAlias::query()->where('slug', $slug)->first();
        if ($alias === null) {
            return null;
        }

        $page = Page::query()
            ->whereKey($alias->page_id)
            ->where('published', true)
            ->with('translations')
            ->first();

        if ($page === null) {
            return null;
        }

        $translation = $this->resolveTranslation($page, $locale, $defaultLocale);
        $canonical = trim((string) ($translation?->slug ?? ''));
        if ($canonical === '' || $canonical === $slug) {
            return null;
        }

        return ['redirect' => ['slug' => $canonical]];
    }

    /** Recursively collect image-like paths from block data arrays. */
    private function collectImagePathsFromBlocks(array $blocks): array
    {
        $paths = [];
        foreach ($blocks as $block) {
            $this->gatherPaths((array) ($block['data'] ?? []), $paths);
        }

        return $paths;
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

    private function rawPathFromUrl(string $url): string
    {
        if (preg_match('/\/storage\/(.+)/', $url, $m)) {
            return $m[1];
        }

        return $url;
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

    private function resolveProjectSection(string $locale, string $defaultLocale): ?array
    {
        $projectsPage = $this->pageRepo->findPublishedByTemplate('project', $locale, $defaultLocale)
            ?? $this->pageRepo->findPublishedByTemplate('projects', $locale, $defaultLocale);

        if ($projectsPage === null) {
            return null;
        }

        $textBlock = $this->resolveBlockData($projectsPage, 'page_text_content', $locale, $defaultLocale);

        $latestPosts = $projectsPage->posts
            ->where('published', true)
            ->sortByDesc(static fn ($post): int => (int) ($post->published_at?->timestamp ?? $post->created_at?->timestamp ?? 0))
            ->take(3)
            ->values();

        $posts = $latestPosts->map(function ($post) use ($locale, $defaultLocale): ?array {
            $t = $this->resolveTranslation($post, $locale, $defaultLocale);

            if ($t === null || trim((string) $t->slug) === '') {
                return null;
            }

            $postBlocks = $this->resolveLocalizedBlocks($post, $locale, $defaultLocale)
                ->sortBy('sort_order')
                ->map(static fn ($block): array => [
                    'type' => FrontendBlockTypeMapper::toFrontend((string) $block->type),
                    'sort_order' => (int) ($block->sort_order ?? 0),
                    'data' => (array) ($block->data ?? []),
                ])
                ->values()
                ->all();

            return [
                'id' => $post->id,
                'slug' => (string) $t->slug,
                'title' => (string) $t->title,
                'excerpt' => (string) (data_get(collect($postBlocks)->firstWhere('type', 'post_intro'), 'data.post_text')
                    ?: $t->excerpt
                    ?: Str::limit(strip_tags((string) $t->content), 180)),
                'feature_image' => $this->toAssetUrl($post->feature_image),
                'category' => (string) ($post->category ?? ''),
                'published_at' => optional($post->published_at ?? $post->created_at)->format('d/m/Y'),
                'blocks' => $postBlocks,
            ];
        })->filter()->values()->all();

        return [
            'title' => (string) ($textBlock['title'] ?? $textBlock['heading'] ?? ''),
            'subtitle' => (string) ($textBlock['subtitle'] ?? $textBlock['description'] ?? $textBlock['text'] ?? ''),
            'posts' => $posts,
        ];
    }

    private function resolveBlogSection(string $locale, string $defaultLocale): ?array
    {
        $blogPage = $this->pageRepo->findPublishedByTemplate('blog', $locale, $defaultLocale);

        if ($blogPage === null) {
            return null;
        }

        $blogTranslation = $this->resolveTranslation($blogPage, $locale, $defaultLocale);
        $pageSlug = (string) ($blogTranslation?->slug ?? '');

        $textBlock = $this->resolveBlockData($blogPage, 'page_text_content', $locale, $defaultLocale);

        $latestPosts = $blogPage->posts
            ->where('published', true)
            ->sortByDesc(static fn ($post): int => (int) ($post->published_at?->timestamp ?? $post->created_at?->timestamp ?? 0))
            ->take(3)
            ->values();

        $posts = $latestPosts->map(function ($post) use ($locale, $defaultLocale): ?array {
            $t = $this->resolveTranslation($post, $locale, $defaultLocale);

            if ($t === null || trim((string) $t->slug) === '') {
                return null;
            }

            $postBlocks = $this->resolveLocalizedBlocks($post, $locale, $defaultLocale)
                ->sortBy('sort_order')
                ->map(static fn ($block): array => [
                    'type' => FrontendBlockTypeMapper::toFrontend((string) $block->type),
                    'sort_order' => (int) ($block->sort_order ?? 0),
                    'data' => (array) ($block->data ?? []),
                ])
                ->values()
                ->all();

            return [
                'id' => $post->id,
                'slug' => (string) $t->slug,
                'title' => (string) $t->title,
                'excerpt' => (string) (data_get(collect($postBlocks)->firstWhere('type', 'post_intro'), 'data.post_text')
                    ?: $t->excerpt
                    ?: Str::limit(strip_tags((string) $t->content), 140)),
                'feature_image' => $this->toAssetUrl($post->feature_image),
                'category' => (string) ($post->category ?? ''),
                'published_at' => optional($post->published_at ?? $post->created_at)->format('d/m/Y'),
                'blocks' => $postBlocks,
            ];
        })->filter()->values()->all();

        return [
            'title' => (string) ($textBlock['title'] ?? $textBlock['heading'] ?? ''),
            'subtitle' => (string) ($textBlock['subtitle'] ?? $textBlock['description'] ?? $textBlock['text'] ?? ''),
            'page_slug' => $pageSlug,
            'posts' => $posts,
        ];
    }

    private function formatReel($reel, string $locale, string $defaultLocale): ?array
    {
        $t = $this->resolveTranslation($reel, $locale, $defaultLocale);

        app()->setLocale($locale);

        $videoUrl = trim((string) ($reel->video_url ?? ''));

        return [
            'id' => $reel->id,
            'title' => $t?->title ?? '',
            'description' => $t?->description ?? '',
            'image' => $this->toAssetUrl($reel->thumbnail_url),
            'video_url' => $videoUrl,
            'category' => $reel->category,
            'category_label' => __("reel_category_{$reel->category}"),
        ];
    }

    /**
     * Map a product flagged "Show in Reels" into the reel payload shape.
     * Ids are offset so they never collide with real reel ids in the UI.
     */
    private function formatProductReel($product, string $locale, string $defaultLocale): ?array
    {
        $t = $this->resolveTranslation($product, $locale, $defaultLocale);

        if ($t === null || trim((string) $t->slug) === '') {
            return null;
        }

        $image = $this->toAssetUrl($product->cover_image)
            ?? $this->firstGalleryImageUrl($this->productBlocks($t));

        if ($image === null) {
            return null;
        }

        $slug = (string) $t->slug;

        return [
            'id' => 1_000_000_000 + (int) $product->id,
            'product_id' => (int) $product->id,
            'title' => (string) $t->title,
            'description' => (string) ($t->excerpt ?? $t->description ?? ''),
            'image' => $image,
            'video_url' => '',
            'category' => 'product',
            'category_label' => __('reel_category_product'),
            'slug' => $slug,
            'url' => '/product/'.$slug,
        ];
    }

    /**
     * Frontend-mapped blocks for a product translation (for gallery fallback).
     *
     * @return array<int, array{type:string, data:array<string,mixed>}>
     */
    private function productBlocks($translation): array
    {
        return collect($translation->blocks ?? [])
            ->map(static fn ($block): array => [
                'type' => FrontendBlockTypeMapper::toFrontend((string) ($block->type ?? '')),
                'data' => (array) ($block->data ?? []),
            ])
            ->values()
            ->all();
    }

    private function resolveRelatedPosts($page, string $locale, string $defaultLocale, ?string $category = null): array
    {
        $query = $page->posts()->where('published', true)->with('translations.blocks');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get()
            ->map(fn ($post) => $this->formatPost($post, $locale, $defaultLocale))
            ->filter()
            ->values()
            ->all();
    }

    private function formatPost($post, string $locale, string $defaultLocale): ?array
    {
        $t = $this->resolveTranslation($post, $locale, $defaultLocale);

        if ($t === null || trim((string) $t->slug) === '') {
            return null;
        }

        $postBlocks = $this->resolveLocalizedBlocks($post, $locale, $defaultLocale)
            ->sortBy('sort_order')
            ->map(static fn ($block): array => [
                'type' => FrontendBlockTypeMapper::toFrontend((string) $block->type),
                'sort_order' => (int) ($block->sort_order ?? 0),
                'data' => (array) ($block->data ?? []),
            ])
            ->values()
            ->all();

        return [
            'id' => $post->id,
            'slug' => (string) $t->slug,
            'title' => (string) $t->title,
            'excerpt' => (string) (data_get(collect($postBlocks)->firstWhere('type', 'post_intro'), 'data.post_text')
                ?: $t->excerpt
                ?: Str::limit(strip_tags((string) $t->content), 140)),
            'feature_image' => $this->toAssetUrl($post->feature_image),
            'category' => $post->category,
            'published_at' => optional($post->published_at ?? $post->created_at)->format('d/m/Y'),
            'blocks' => $postBlocks,
        ];
    }

    private function formatProduct($product, string $locale, string $defaultLocale): ?array
    {
        $t = $this->resolveTranslation($product, $locale, $defaultLocale);

        if ($t === null) {
            return null;
        }

        $blocks = collect($t->blocks ?? [])
            ->sortBy('sort_order')
            ->map(fn ($block) => [
                'type' => FrontendBlockTypeMapper::toFrontend((string) $block->type),
                'sort_order' => (int) ($block->sort_order ?? 0),
                'data' => (array) ($block->data ?? []),
            ])
            ->values()
            ->all();

        $onSale = (bool) $product->on_sale;
        $salePrice = $onSale && $product->sale_price !== null ? (float) $product->sale_price : null;
        $basePrice = $this->resolveDisplayPrice($product, $t);

        $featureImage = $this->toAssetUrl($product->cover_image)
            ?? $this->firstGalleryImageUrl($blocks);

        return [
            'id' => $product->id,
            'slug' => (string) $t->slug,
            'title' => (string) $t->title,
            'price' => $salePrice ?? $basePrice,
            'old_price' => $salePrice !== null ? $basePrice : null,
            'on_sale' => $onSale,
            'is_featured' => (bool) $product->is_featured,
            'brand' => (string) ($product->brand ?? ''),
            'stock' => (int) ($product->stock ?? 0),
            'is_ordered' => (bool) ($product->is_ordered ?? false),
            'is_rented' => (bool) ($product->is_rented ?? false),
            'feature_image' => $featureImage,
            'category' => (string) ($t->category ?? $product->category ?? ''),
            'colors' => collect($product->colors ?? [])->values()->all(),
            'blocks' => $blocks,
        ];
    }

    /**
     * @param  array<int, array{type:string, data:array<string,mixed>}>  $blocks
     */
    private function firstGalleryImageUrl(array $blocks): ?string
    {
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') !== 'product_gallery') {
                continue;
            }

            $images = $block['data']['product_images'] ?? $block['data']['images'] ?? null;
            if (! is_array($images)) {
                continue;
            }

            foreach ($images as $image) {
                $url = $this->toAssetUrl($image);
                if ($url !== null) {
                    return $url;
                }
            }
        }

        return null;
    }

    private function toAssetUrl(mixed $path): ?string
    {
        $value = trim((string) $path);

        if ($value === '') {
            return null;
        }

        if (
            str_starts_with($value, 'http://') ||
            str_starts_with($value, 'https://') ||
            str_starts_with($value, '/')
        ) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    private function resolveDisplayPrice($product, mixed $translation): float
    {
        $basePrice = (float) ($product->price ?? 0);

        if ($basePrice > 0) {
            return $basePrice;
        }

        $introBlock = collect($translation?->blocks ?? [])->first(
            static fn ($block): bool => (string) ($block->type ?? '') === 'product_intro'
        );

        $fallbackPrice = (float) data_get($introBlock, 'data.price_gel', 0);

        return $fallbackPrice > 0 ? $fallbackPrice : 0.0;
    }
}
