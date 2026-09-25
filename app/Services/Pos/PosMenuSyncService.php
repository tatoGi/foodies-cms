<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\PosDevice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Services\SlugService;
use App\Services\Website\RevalidateFrontendService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Applies a full FoodEase menu snapshot. POS-owned fields (name, price, availability, category,
 * ingredients, add-ons) are overwritten; CMS-owned fields (slug, SEO, content, publishing)
 * are only written when a record is first created. A POS product cover follows the POS photo:
 * a new file replaces it, and deleting the file in the POS clears it. See docs/INTEGRATION_MASTER_PLAN.md section 3.
 */
class PosMenuSyncService
{
    private const SOURCE = 'pos';

    public function __construct(
        private readonly SlugService $slugs,
        private readonly RevalidateFrontendService $frontend,
    ) {}

    /**
     * @param  array{categories?: array<int, array<string, mixed>>, items?: array<int, array<string, mixed>>}  $snapshot
     * @return array{categories: int, items: int}
     */
    public function syncSnapshot(PosDevice $device, array $snapshot): array
    {
        $categories = $snapshot['categories'] ?? [];
        $items = $snapshot['items'] ?? [];

        DB::transaction(function () use ($categories, $items): void {
            $categoryIds = [];
            foreach ($categories as $category) {
                $categoryIds[(int) $category['external_id']] = $this->syncCategory($category)->id;
            }
            ProductCategory::query()
                ->where('external_source', self::SOURCE)
                ->whereNotIn('external_id', array_keys($categoryIds))
                ->update(['is_active' => false]);

            foreach ($items as $item) {
                $this->syncItem($item, $categoryIds);
            }
            Product::query()
                ->where('external_source', self::SOURCE)
                ->whereNotIn('external_id', array_map(fn (array $item): int => (int) $item['external_id'], $items))
                ->update(['is_active' => false, 'published' => false]);
        });

        $device->update(['last_sync_at' => now()]);

        $this->frontend->revalidate($this->revalidateTags($items));

        return ['categories' => count($categories), 'items' => count($items)];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<string>
     */
    private function revalidateTags(array $items): array
    {
        $externalIds = array_map(fn (array $item): int => (int) $item['external_id'], $items);
        $slugs = $externalIds === []
            ? []
            : ProductTranslation::query()
                ->whereHas('product', function ($query) use ($externalIds): void {
                    $query->where('external_source', self::SOURCE)->whereIn('external_id', $externalIds);
                })
                ->pluck('slug')
                ->filter(fn (mixed $slug): bool => is_string($slug) && $slug !== '')
                ->unique()
                ->map(fn (string $slug): string => 'product:'.$slug)
                ->values()
                ->all();

        return array_merge(['menu', 'status'], $slugs);
    }

    /** @param  array<string, mixed>  $data */
    private function syncCategory(array $data): ProductCategory
    {
        $category = ProductCategory::query()->updateOrCreate(
            ['external_source' => self::SOURCE, 'external_id' => $data['external_id']],
            [
                'sort_order' => $data['sort_order'] ?? 0,
                'show_on_menu_board' => (bool) ($data['show_on_menu_board'] ?? false),
                'is_active' => true,
            ],
        );

        foreach ($data['name'] as $locale => $name) {
            $translation = $category->translations()->firstOrNew(['locale' => $locale]);
            $translation->name = $name;
            $translation->description = $data['description'][$locale] ?? $translation->description;
            $translation->slug ??= $this->slugs->makeUnique(
                $this->slugs->generate($name) ?: 'category-'.$category->external_id.'-'.$locale,
                ProductCategoryTranslation::class,
            );
            $translation->save();
        }

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $categoryIds  POS category id => CMS category id
     */
    private function syncItem(array $data, array $categoryIds): void
    {
        $categoryExternalId = $data['category_ids'][0] ?? null;

        $product = Product::query()->firstOrNew(['external_source' => self::SOURCE, 'external_id' => $data['external_id']]);
        $isNew = ! $product->exists;
        $product->sku ??= 'pos-'.$data['external_id'];
        $product->fill([
            'price' => $data['price'],
            'is_available' => (bool) ($data['is_available'] ?? true),
            'is_active' => true,
            'show_on_menu_board' => (bool) ($data['show_on_menu_board'] ?? false),
            'product_category_id' => $categoryExternalId !== null ? ($categoryIds[(int) $categoryExternalId] ?? null) : null,
        ]);
        if ($isNew) {
            $product->published = true;
        }
        $product->save();
        $this->syncCover($product, $data['image'] ?? null);

        foreach ($data['name'] as $locale => $title) {
            $translation = $product->translations()->firstOrNew(['locale' => $locale]);
            $translation->title = $title;
            $translation->excerpt ??= $data['description'][$locale] ?? null;
            $translation->slug ??= $this->slugs->makeUnique(
                $this->slugs->generate($title) ?: 'pos-'.$data['external_id'].'-'.$locale,
                ProductTranslation::class,
            );
            $translation->save();
        }

        $this->syncChildren($product, 'ingredients', $data['ingredients'] ?? [], fn (array $row): array => [
            'name' => $row['name'],
            'is_removable' => (bool) ($row['is_removable'] ?? true),
        ]);
        $this->syncChildren($product, 'addons', $data['addons'] ?? [], fn (array $row): array => [
            'name' => $row['name'],
            'price' => $row['price'],
        ]);
    }

    private function syncCover(Product $product, mixed $image): void
    {
        if (! is_array($image)) {
            $this->clearSyncedCover($product);

            return;
        }

        $extension = match ((string) ($image['mime'] ?? '')) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };
        $binary = base64_decode((string) ($image['data'] ?? ''), true);
        if ($extension === null || ! is_string($binary) || $binary === '' || strlen($binary) > 5 * 1024 * 1024) {
            return;
        }

        $path = 'pos-menu/'.$product->external_id.'.'.$extension;
        $previous = (string) $product->cover_image;
        if ($previous !== '' && $previous !== $path && str_starts_with($previous, 'pos-menu/')) {
            Storage::disk('public')->delete($previous);
        }
        Storage::disk('public')->put($path, $binary);
        if ($product->cover_image !== $path) {
            $product->cover_image = $path;
            $product->save();
        }
    }

    private function clearSyncedCover(Product $product): void
    {
        $previous = (string) $product->cover_image;
        if ($previous === '' || ! str_starts_with($previous, 'pos-menu/')) {
            return;
        }

        Storage::disk('public')->delete($previous);
        $product->cover_image = null;
        $product->save();
    }

    /**
     * @param  'ingredients'|'addons'  $relation
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): array<string, mixed>  $attributes
     */
    private function syncChildren(Product $product, string $relation, array $rows, callable $attributes): void
    {
        foreach ($rows as $row) {
            $product->{$relation}()->updateOrCreate(['external_id' => $row['external_id']], $attributes($row));
        }

        $product->{$relation}()
            ->whereNotIn('external_id', array_map(fn (array $row): int => (int) $row['external_id'], $rows))
            ->delete();
    }
}
