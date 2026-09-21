<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Product;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Traits\ResolvesTranslationBlocks;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebsiteProductService
{
    use ResolvesTranslationBlocks;

    public function __construct(
        private readonly LanguageRepositoryInterface $langRepo,
    ) {}

    public function buildProductListData(?string $requestedLocale = null): array
    {
        $defaultLocale = strtolower($this->langRepo->defaultLocale());
        $locale = strtolower(trim((string) $requestedLocale)) ?: $defaultLocale;

        $products = Product::query()
            ->where('published', true)
            ->where('is_active', true)
            ->with('translations.blocks')
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->get()
            ->map(function ($product) use ($locale, $defaultLocale): ?array {
                $t = $this->resolveTranslation($product, $locale, $defaultLocale);

                if ($t === null) {
                    return null;
                }

                $onSale = (bool) $product->on_sale;
                $salePrice = $onSale && $product->sale_price !== null ? (float) $product->sale_price : null;

                $excerpt = trim((string) ($t->excerpt ?? ''));
                if ($excerpt === '') {
                    $excerpt = Str::limit(strip_tags((string) ($t->content ?? '')), 180);
                }

                $blocks = collect($t->blocks ?? [])
                    ->sortBy('sort_order')
                    ->map(static fn ($block): array => [
                        'type' => (string) ($block->type ?? ''),
                        'sort_order' => (int) ($block->sort_order ?? 0),
                        'data' => (array) ($block->data ?? []),
                    ])
                    ->values()
                    ->all();

                $featureImage = $this->toAssetUrl($product->feature_image)
                    ?? $this->firstGalleryImageUrl($blocks);

                return [
                    'id' => (int) $product->id,
                    'slug' => (string) $t->slug,
                    'title' => (string) $t->title,
                    'excerpt' => $excerpt,
                    'price' => $salePrice ?? $this->resolveDisplayPrice($product, $t),
                    'old_price' => $salePrice !== null ? $this->resolveDisplayPrice($product, $t) : null,
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
            })
            ->filter()
            ->values()
            ->all();

        return ['products' => $products];
    }

    public function buildProductData(string $slug, ?string $requestedLocale = null): ?array
    {
        $defaultLocale = strtolower($this->langRepo->defaultLocale());
        $locale = strtolower(trim((string) $requestedLocale)) ?: $defaultLocale;

        // Find product by slug in translations
        $product = Product::query()
            ->where('published', true)
            ->where('is_active', true)
            ->with('translations.blocks')
            ->whereHas('translations', function ($q) use ($slug) {
                $q->where('slug', $slug);
            })
            ->first();

        if ($product === null) {
            return null;
        }

        $resolvedTranslation = $this->resolveTranslation($product, $locale, $defaultLocale);

        // All localized blocks for this product
        $blocks = collect($resolvedTranslation?->blocks ?? [])
            ->sortBy('sort_order')
            ->map(static fn ($block): array => [
                'type' => (string) ($block->type ?? ''),
                'sort_order' => (int) ($block->sort_order ?? 0),
                'data' => (array) ($block->data ?? []),
            ])
            ->values()
            ->all();

        $onSale = (bool) $product->on_sale;
        $salePrice = $onSale && $product->sale_price !== null ? (float) $product->sale_price : null;
        $basePrice = $this->resolveDisplayPrice($product, $resolvedTranslation);

        $excerpt = trim((string) ($resolvedTranslation?->excerpt ?? ''));
        if ($excerpt === '') {
            $excerpt = Str::limit(strip_tags((string) ($resolvedTranslation?->content ?? '')), 200);
        }

        return [
            'product' => [
                'id' => (int) $product->id,
                'slug' => (string) ($resolvedTranslation?->slug ?? $slug),
                'title' => (string) $resolvedTranslation?->title,
                'description' => $excerpt,
                'content' => (string) ($resolvedTranslation?->content ?? ''),
                'price' => $salePrice ?? $basePrice,
                'old_price' => $salePrice !== null ? $basePrice : null,
                'on_sale' => $onSale,
                'is_featured' => (bool) $product->is_featured,
                'brand' => (string) ($product->brand ?? ''),
                'category' => (string) ($resolvedTranslation?->category ?? $product->category ?? ''),
                'cover_image' => $this->toAssetUrl($product->cover_image),
                'feature_image' => $this->toAssetUrl($product->feature_image),
                'gallery' => collect($product->gallery ?? [])->map(fn ($image) => $this->toAssetUrl($image))->filter()->values()->all(),
                'colors' => collect($product->colors ?? [])->values()->all(),
                'blocks' => $blocks,
            ],
            'seo' => [
                'meta_title' => $resolvedTranslation?->meta_title ?: $resolvedTranslation?->title,
                'meta_description' => $resolvedTranslation?->meta_description,
                'keywords' => $resolvedTranslation?->keywords,
                'canonical_url' => $resolvedTranslation?->canonical_url,
            ],
        ];
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
     * @param  array<int, array{type:string, data:array}>  $blocks
     */
    private function firstGalleryImageUrl(array $blocks): ?string
    {
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') !== 'product_gallery') {
                continue;
            }

            $images = $block['data']['images'] ?? $block['data']['product_images'] ?? null;
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
}
