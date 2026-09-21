<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginateWithTranslations(int $perPage = 15, string $search = ''): LengthAwarePaginator
    {
        $query = Product::query()
            ->with('translations')
            ->orderByDesc('is_featured')
            ->orderByDesc('id');

        if (trim($search) !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('id', $search)
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhereHas('translations', function ($translationQuery) use ($search) {
                        $translationQuery->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->paginate($perPage)
            ->withQueryString();
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
