<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function paginateWithTranslations(int $perPage = 15, string $search = '', ?string $category = null): LengthAwarePaginator;

    /**
     * @return list<array{id: int, name: string, count: int}>
     */
    public function categoriesWithProductCounts(string $search, string $locale): array;

    public function countWithoutCategory(string $search): int;

    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function delete(Product $product): bool;

    public function nextSortOrder(): int;

    public function reorderByIds(array $orderedIds, int $page = 1, int $perPage = 15): void;
}
