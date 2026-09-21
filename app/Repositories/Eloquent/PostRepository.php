<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PostRepository implements PostRepositoryInterface
{
    public function paginateWithTranslations(int $perPage = 15): LengthAwarePaginator
    {
        return Post::query()
            ->with(['translations', 'pages.translations'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Post
    {
        return Post::query()->create($data);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);

        return $post;
    }

    public function delete(Post $post): bool
    {
        return (bool) $post->delete();
    }

    public function reorderByIds(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                Post::query()->where('id', (int) $id)->update(['sort_order' => $position]);
            }
        });
    }
}
