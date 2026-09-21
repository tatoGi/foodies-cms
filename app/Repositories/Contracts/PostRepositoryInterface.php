<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    public function paginateWithTranslations(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Post;

    public function update(Post $post, array $data): Post;

    public function delete(Post $post): bool;

    public function reorderByIds(array $orderedIds): void;
}
