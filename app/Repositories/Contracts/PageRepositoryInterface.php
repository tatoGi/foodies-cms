<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PageRepositoryInterface
{
    public function paginateWithTranslationsAndTemplate(int $perPage = 15): LengthAwarePaginator;

    /**
     * @return array<int, array{id:int,title:string}>
     */
    public function allExcept(?int $exceptId = null): array;

    public function create(array $data): Page;

    public function update(Page $page, array $data): Page;

    public function clearHomeFlagExcept(?int $exceptPageId = null): void;

    public function delete(Page $page): bool;

    public function reorderByIds(array $orderedIds): void;

    /**
     * @return Collection<int, Page>
     */
    public function allWithTranslationsAndTemplateOrdered(): Collection;

    /**
     * @return array<int, int>
     */
    public function allIds(): array;

    /**
     * @param  array<int, array{id:int,parent_id:?int,sort_order:int}>  $updates
     */
    public function updateHierarchy(array $updates): void;

    /**
     * @return array<int, array{slug:string,name:string}>
     */
    public function allTemplates(): array;
}
