<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\BlockTypeDefinition;
use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use Illuminate\Support\Collection;

class BlockTypeRepository implements BlockTypeRepositoryInterface
{
    /**
     * @return Collection<int, BlockTypeDefinition>
     */
    public function allForAdminList(): Collection
    {
        return BlockTypeDefinition::query()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, BlockTypeDefinition>
     */
    public function getEnabledForScope(string $scope): Collection
    {
        return BlockTypeDefinition::query()
            ->where('scope', $scope)
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function findOrFail(int|string $id): BlockTypeDefinition
    {
        return BlockTypeDefinition::query()->findOrFail($id);
    }

    public function create(array $data): BlockTypeDefinition
    {
        return BlockTypeDefinition::query()->create($data);
    }

    public function update(BlockTypeDefinition $blockType, array $data): BlockTypeDefinition
    {
        $blockType->update($data);

        return $blockType;
    }

    public function delete(BlockTypeDefinition $blockType): bool
    {
        return (bool) $blockType->delete();
    }

    public function maxSortOrder(): int
    {
        return (int) (BlockTypeDefinition::query()->max('sort_order') ?? 0);
    }
}
