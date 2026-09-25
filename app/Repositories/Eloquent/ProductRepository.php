<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginateWithTranslations(int $perPage = 15, string $search = '', ?string $category = null): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['translations', 'productCategory.translations'])
            ->orderByDesc('is_featured')
            ->orderByDesc('id');

        $this->applySearch($query, $search);
        $this->applyCategory($query, $category);

        return $query->paginate($perPage)
            ->withQueryString();
    }

    public function categoriesWithProductCounts(string $search, string $locale): array
    {
        return ProductCategory::query()
            ->with('translations')
            ->withCount(['products as products_count' => function (Builder $query) use ($search): void {
                $this->applySearch($query, $search);
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (ProductCategory $category) use ($locale): array {
                $translations = $category->translations;

                return [
                    'id' => $category->id,
                    'name' => (string) ($translations->firstWhere('locale', $locale)?->name
                        ?? $translations->firstWhere('locale', 'ka')?->name
                        ?? $translations->first()?->name
                        ?? '#'.$category->id),
                    'count' => (int) $category->products_count,
                ];
            })
            ->all();
    }

    public function countWithoutCategory(string $search): int
    {
        $query = Product::query()->whereNull('product_category_id');
        $this->applySearch($query, $search);

        return $query->count();
    }

    private function applySearch(Builder $query, string $search): void
    {
        if (trim($search) === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            $builder->where('id', $search)
                ->orWhere('sku', 'like', '%'.$search.'%')
                ->orWhereHas('translations', function (Builder $translationQuery) use ($search): void {
                    $translationQuery->where('title', 'like', '%'.$search.'%');
                });
        });
    }

    private function applyCategory(Builder $query, ?string $category): void
    {
        if ($category === null || $category === '' || $category === 'all') {
            return;
        }

        if ($category === 'none') {
            $query->whereNull('product_category_id');

            return;
        }

        if (ctype_digit($category)) {
            $query->where('product_category_id', (int) $category);
        }
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function delete(Product $product): bool
    {
        // Permanently delete translations and blocks, then force-delete the product.
        foreach ($product->translations()->with('blocks')->get() as $translation) {
            $translation->blocks()->forceDelete();
            $translation->forceDelete();
        }

        return (bool) $product->forceDelete();
    }

    public function nextSortOrder(): int
    {
        return (int) (Product::query()->max('sort_order') ?? 0) + 1;
    }

    public function reorderByIds(array $orderedIds, int $page = 1, int $perPage = 15): void
    {
        DB::transaction(function () use ($orderedIds, $page, $perPage): void {
            // Full list in current order (excluding the page being reordered)
            $allIds = Product::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->toArray();

            $offset = ($page - 1) * $perPage;

            // Rebuild: items before this page + new order for this page + items after
            $before = array_slice($allIds, 0, $offset);
            $after = array_slice($allIds, $offset + count($orderedIds));
            $newOrder = array_merge($before, array_map('intval', $orderedIds), $after);

            foreach ($newOrder as $position => $id) {
                Product::query()->where('id', $id)->update(['sort_order' => $position + 1]);
            }
        });
    }
}
