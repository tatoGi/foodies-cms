<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\PosDevice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Services\SlugService;
use Illuminate\Support\Facades\DB;

/**
 * Applies a full FoodEase menu snapshot. POS-owned fields (name, price, availability, category,
 * ingredients, add-ons) are overwritten; CMS-owned fields (slug, SEO, content, images, publishing)
 * are only written when a record is first created. See docs/INTEGRATION_MASTER_PLAN.md section 3.
 */
class PosMenuSyncService
{
    private const SOURCE = 'pos';

    public function __construct(private readonly SlugService $slugs) {}

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

        return ['categories' => count($categories), 'items' => count($items)];
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
        $product->sku ??= 'pos-'.$data['external_id'];
        $product->fill([
            'price' => $data['price'],
            'is_available' => (bool) ($data['is_available'] ?? true),
            'is_active' => true,
            'show_on_menu_board' => (bool) ($data['show_on_menu_board'] ?? false),
            'product_category_id' => $categoryExternalId !== null ? ($categoryIds[(int) $categoryExternalId] ?? null) : null,
        ]);
        $product->save();

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
