<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\WebsiteMenuRepositoryInterface;
use App\Repositories\Contracts\WebsitePageRepositoryInterface;
use App\Support\FrontendBlockTypeMapper;
use App\Traits\ResolvesTranslationBlocks;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomepageService
{
    use ResolvesTranslationBlocks;

    public function __construct(
        private readonly WebsitePageRepositoryInterface $pageRepo,
        private readonly WebsiteMenuRepositoryInterface $menuRepo,
        private readonly LanguageRepositoryInterface $langRepo,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $locale = app()->getLocale();
        $defaultLocale = $this->langRepo->defaultLocale();

        $page = $this->pageRepo->findHomepage($locale, $defaultLocale);

        $homepage = null;
        $template = 'home';

        if ($page instanceof Page) {
            $template = trim((string) ($page->template ?: 'home'));

            $homepage = $this->buildHomepagePayload($page, $locale, $defaultLocale);
        }

        $projectsPage = $this->pageRepo->findPublishedByTemplate('project', $locale, $defaultLocale);

        $latestProjectPayload = null;

        if ($projectsPage instanceof Page) {
            $latestPost = $projectsPage->posts
                ->where('published', true)
                ->sortByDesc(static fn (Post $post): int => (int) ($post->published_at?->timestamp ?? $post->created_at?->timestamp ?? 0))
                ->first();

            if ($latestPost instanceof Post) {
                $latestProjectPayload = $this->mapPost($latestPost, $locale, $defaultLocale);
            }
        }

        $products = $homepage['relations']['products'] ?? [];
        if ($products === []) {
            $products = $this->featuredProducts($locale, $defaultLocale);
        }

        return [
            'template' => $template,
            'homepage' => $homepage,
            'latest_project' => $latestProjectPayload,
            'relations' => [
                'posts' => $homepage['relations']['posts'] ?? [],
                'products' => $products,
                'children' => $homepage['relations']['children'] ?? [],
                'parent' => $homepage['relations']['parent'] ?? null,
            ],

        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveBannerData(Page $page, string $locale, string $fallbackLocale): array
    {
        $banner = $this->resolveBlockData($page, 'banner', $locale, $fallbackLocale);

        if ($banner === []) {
            $banner = $this->resolveBlockData($page, 'main_banner', $locale, $fallbackLocale);
        }

        if ($banner === []) {
            $banner = $this->resolveBlockData($page, 'page_hero', $locale, $fallbackLocale);
        }

        if (isset($banner['banner_image'])) {
            $banner['banner_image'] = $this->toAssetUrl($banner['banner_image']);
        }

        if (isset($banner['image'])) {
            $banner['image'] = $this->toAssetUrl($banner['image']);
        }

        return $banner;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHomepagePayload(Page $page, string $locale, string $fallbackLocale): array
    {
        $translation = $this->resolveTranslation($page, $locale, $fallbackLocale);
        $title = trim((string) ($translation?->title ?: config('app.name')));
        $slug = trim((string) ($translation?->slug ?? ''));

        $posts = $page->posts
            ->where('published', true)
            ->sortByDesc(static fn (Post $post): int => (int) ($post->published_at?->timestamp ?? $post->created_at?->timestamp ?? 0))
            ->map(fn (Post $post): ?array => $this->mapPost($post, $locale, $fallbackLocale))
            ->filter()
            ->values()
            ->all();

        $products = $page->products
            ->where('published', true)
            ->sortByDesc(static fn (Product $product): int => (int) ($product->published_at?->timestamp ?? $product->created_at?->timestamp ?? 0))
            ->map(fn (Product $product): ?array => $this->mapProduct($product, $locale, $fallbackLocale))
            ->filter()
            ->values()
            ->all();

        if ($products === []) {
            $products = $this->featuredProducts($locale, $fallbackLocale);
        }

        $children = $page->children
            ->map(fn (Page $child): ?array => $this->mapPageSummary($child, $locale, $fallbackLocale))
            ->filter()
            ->values()
            ->all();

        return [
            'id' => (int) $page->id,
            'template' => (string) ($page->template ?: 'home'),
            'is_home' => (bool) $page->is_home,
            'sort_order' => (int) ($page->sort_order ?? 0),
            'feature_image' => $this->toAssetUrl($page->feature_image),
            'title' => $title,
            'slug' => $slug,
            'description' => (string) ($translation?->description ?? ''),
            'blocks' => $this->mapBlocks($page, $locale, $fallbackLocale),
            'relations' => [
                'posts' => $posts,
                'products' => $products,
                'children' => $children,
                'parent' => $page->parent instanceof Page
                    ? $this->mapPageSummary($page->parent, $locale, $fallbackLocale)
                    : null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapPost(Post $post, string $locale, string $fallbackLocale): ?array
    {
        $translation = $this->resolveTranslation($post, $locale, $fallbackLocale);
        if ($translation === null || trim((string) $translation->slug) === '') {
            return null;
        }

        $excerpt = trim((string) ($translation->excerpt ?? ''));
        if ($excerpt === '') {
            $excerpt = Str::limit(strip_tags((string) ($translation->content ?? '')), 180);
        }

        return [
            'id' => (int) $post->id,
            'title' => (string) $translation->title,
            'slug' => (string) $translation->slug,
            'excerpt' => $excerpt,
            'category' => (string) ($post->category ?? ''),
            'feature_image' => $this->toAssetUrl($post->feature_image),
            'published_at' => $this->formatDateTime($post->published_at ?? $post->created_at),
            'blocks' => $this->mapBlocks($post, $locale, $fallbackLocale),
        ];
    }

    /**
     * Featured products used when the homepage has no manually attached products.
     *
     * @return array<int, array<string, mixed>>
     */
    private function featuredProducts(string $locale, string $fallbackLocale): array
    {
        $base = Product::query()
            ->where('published', true)
            ->where('is_active', true)
            ->with(['translations.blocks', 'productCategory.translations']);

        $featured = (clone $base)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        $rows = $featured->isNotEmpty()
            ? $featured
            : (clone $base)->orderBy('sort_order')->orderBy('id')->limit(12)->get();

        return $rows
            ->map(fn (Product $product): ?array => $this->mapProduct($product, $locale, $fallbackLocale))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapProduct(Product $product, string $locale, string $fallbackLocale): ?array
    {
        $translation = $this->resolveTranslation($product, $locale, $fallbackLocale);
        if ($translation === null || trim((string) $translation->slug) === '') {
            return null;
        }

        $excerpt = trim((string) ($translation->excerpt ?? ''));
        if ($excerpt === '') {
            $excerpt = Str::limit(strip_tags((string) ($translation->content ?? '')), 180);
        }

        $product->loadMissing('productCategory.translations');
        $blocks = $this->mapBlocks($product, $locale, $fallbackLocale);

        $featureImage = $this->toAssetUrl($product->feature_image)
            ?? $this->toAssetUrl($product->cover_image)
            ?? $this->firstGalleryImageUrl($blocks);

        return [
            'id' => (int) $product->id,
            'title' => (string) $translation->title,
            'slug' => (string) $translation->slug,
            'excerpt' => $excerpt,
            'category' => $this->categoryName($product, $locale, $fallbackLocale),
            'is_available' => (bool) $product->is_available,
            'price' => $this->resolveDisplayPrice($product, $translation),
            'stock' => (int) $product->stock,
            'is_featured' => (bool) $product->is_featured,
            'cover_image' => $this->toAssetUrl($product->cover_image),
            'feature_image' => $featureImage,
            'published_at' => $this->formatDateTime($product->published_at ?? $product->created_at),
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

    private function categoryName(Product $product, string $locale, string $fallbackLocale): string
    {
        $translations = $product->productCategory?->translations;
        if ($translations !== null) {
            $translation = $translations->firstWhere('locale', $locale)
                ?? $translations->firstWhere('locale', $fallbackLocale)
                ?? $translations->first();
            $name = trim((string) ($translation?->name ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return (string) ($product->category ?? '');
    }

    private function resolveDisplayPrice(Product $product, mixed $translation): float
    {
        $basePrice = (float) $product->price;

        if ($basePrice > 0) {
            return $basePrice;
        }

        $introBlock = collect($translation?->blocks ?? [])->first(
            static fn ($block): bool => (string) ($block->type ?? '') === 'product_intro'
        );

        $fallbackPrice = (float) data_get($introBlock, 'data.price_gel', 0);

        return $fallbackPrice > 0 ? $fallbackPrice : 0.0;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapPageSummary(Page $page, string $locale, string $fallbackLocale): ?array
    {
        $translation = $this->resolveTranslation($page, $locale, $fallbackLocale);
        if ($translation === null || trim((string) $translation->slug) === '') {
            return null;
        }

        return [
            'id' => (int) $page->id,
            'template' => (string) ($page->template ?: 'default'),
            'title' => (string) ($translation->title ?: config('app.name')),
            'slug' => (string) $translation->slug,
            'description' => (string) ($translation->description ?? ''),
            'feature_image' => $this->toAssetUrl($page->feature_image),
        ];
    }

    /**
     * @return array<int, array{type:string,sort_order:int,data:array<string,mixed>}>
     */
    private function mapBlocks(mixed $model, string $locale, string $fallbackLocale): array
    {
        return $this->resolveLocalizedBlocks($model, $locale, $fallbackLocale)
            ->sortBy('sort_order')
            ->map(static function ($block): array {
                return [
                    'type' => FrontendBlockTypeMapper::toFrontend((string) ($block->type ?? '')),
                    'sort_order' => (int) ($block->sort_order ?? 0),
                    'data' => (array) ($block->data ?? []),
                ];
            })
            ->values()
            ->all();
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

    private function formatDateTime(mixed $date): ?string
    {
        if (! $date instanceof DateTimeInterface) {
            return null;
        }

        return $date->format('Y-m-d H:i:s');
    }
}
