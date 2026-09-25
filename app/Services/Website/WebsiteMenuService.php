<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Language;
use App\Models\PosDevice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class WebsiteMenuService
{
    /**
     * @return array{locale: string, categories: array<int, array<string, mixed>>}
     */
    public function menu(string $requestedLocale, bool $featuredOnly = false): array
    {
        [$locale, $fallback] = $this->locales($requestedLocale);

        $products = Product::query()
            ->where('published', true)
            ->where('is_active', true)
            // The home page "best dishes" section shows only dishes marked featured in the CMS.
            ->when($featuredOnly, fn ($query) => $query->where('is_featured', true))
            ->with(['translations', 'addons', 'ingredients', 'productCategory.translations'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = $products
            ->groupBy(fn (Product $product): int => (int) ($product->product_category_id ?? 0))
            ->map(function (Collection $group) use ($locale, $fallback): array {
                /** @var Product $first */
                $first = $group->first();
                $category = $first->productCategory;
                $translation = $category instanceof ProductCategory
                    ? $this->pick($category->translations, $locale, $fallback)
                    : null;

                return [
                    'slug' => $translation?->slug ?? 'menu',
                    'name' => $translation?->name ?? '',
                    'description' => $translation?->description ?? null,
                    'sort_order' => $category?->sort_order ?? 0,
                    'image' => $this->assetUrl($category?->image),
                    'products' => $group
                        ->map(fn (Product $product): array => $this->productPayload($product, $locale, $fallback))
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();

        return ['locale' => $locale, 'categories' => $categories];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function category(string $slug, string $requestedLocale): ?array
    {
        $menu = $this->menu($requestedLocale);

        foreach ($menu['categories'] as $category) {
            if ($category['slug'] === $slug) {
                return $category;
            }
        }

        $exists = ProductCategoryTranslation::query()->where('slug', $slug)->exists();

        return $exists ? ['slug' => $slug, 'name' => '', 'description' => null, 'sort_order' => 0, 'image' => null, 'products' => []] : null;
    }

    /**
     * @return array{open: bool, accepting_online_orders: bool, pos_online: bool, prep_time: int|null}
     */
    public function status(): array
    {
        $online = PosDevice::query()
            ->where('is_active', true)
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->exists();

        return [
            'open' => true,
            'accepting_online_orders' => $online,
            'pos_online' => $online,
            'prep_time' => null,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function locales(string $requested): array
    {
        $active = Language::query()->active()->orderBy('sort_order')->get();
        $fallback = strtolower((string) ($active->firstWhere('is_default', true)->code ?? $active->first()->code ?? 'ka'));
        $requested = strtolower(trim($requested));
        $locale = $active->contains(fn (Language $language): bool => $language->code === $requested)
            ? $requested
            : $fallback;

        return [$locale, $fallback];
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product, string $locale, string $fallback): array
    {
        $translation = $this->pick($product->translations, $locale, $fallback);
        $onSale = (bool) $product->on_sale && $product->sale_price !== null;

        return [
            'id' => (int) $product->id,
            'slug' => (string) ($translation?->slug ?? ''),
            'title' => (string) ($translation?->title ?? ''),
            'excerpt' => $translation?->excerpt ?? null,
            'price' => $this->money($product->price),
            'sale_price' => $onSale ? $this->money($product->sale_price) : null,
            'is_available' => (bool) $product->is_available,
            'image' => $this->assetUrl($product->cover_image),
            'ingredients' => $product->ingredients->map(fn ($row): array => [
                'name' => $this->localizedName((array) $row->name, $locale, $fallback),
                'is_removable' => (bool) $row->is_removable,
            ])->values()->all(),
            'addons' => $product->addons->map(fn ($row): array => [
                'name' => $this->localizedName((array) $row->name, $locale, $fallback),
                'price' => $this->money($row->price),
            ])->values()->all(),
        ];
    }

    private function pick(Collection $translations, string $locale, string $fallback): mixed
    {
        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', $fallback)
            ?? $translations->first();
    }

    /**
     * @param  array<string, mixed>  $name
     */
    private function localizedName(array $name, string $locale, string $fallback): string
    {
        foreach ([$locale, $fallback] as $code) {
            $value = trim((string) ($name[$code] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        foreach ($name as $value) {
            $text = trim((string) $value);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function assetUrl(mixed $path): ?string
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
}
