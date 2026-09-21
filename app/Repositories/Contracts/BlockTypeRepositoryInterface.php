<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\BlockTypeDefinition;
use Illuminate\Support\Collection;

interface BlockTypeRepositoryInterface
{
    /**
     * @return Collection<int, \App\Models\BlockTypeDefinition>
     */
    public function getEnabledForScope(string $scope): Collection;

    /**
     * @return Collection<int, BlockTypeDefinition>
     */
    public function allForAdminList(): Collection;

    public function findOrFail(int|string $id): BlockTypeDefinition;

    public function create(array $data): BlockTypeDefinition;

    public function update(BlockTypeDefinition $blockType, array $data): BlockTypeDefinition;

    public function delete(BlockTypeDefinition $blockType): bool;

    public function maxSortOrder(): int;
}
