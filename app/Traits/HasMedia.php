<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasMedia
{
    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables', 'mediable_id', 'media_id')
            ->withPivot(['collection', 'sort_order'])
            ->withTimestamps();
    }

    public function attachMedia(int $mediaId, string $collection = 'default'): static
    {
        $nextOrder = (int) $this->media()
            ->wherePivot('collection', $collection)
            ->max('mediables.sort_order');

        $this->media()->syncWithoutDetaching([
            $mediaId => [
                'collection' => $collection,
                'sort_order' => $nextOrder + 1,
            ],
        ]);

        return $this;
    }

    public function detachMedia(int $mediaId, ?string $collection = null): static
    {
        if ($collection === null) {
            $this->media()->detach($mediaId);

            return $this;
        }

        $this->media()
            ->wherePivot('collection', $collection)
            ->detach($mediaId);

        return $this;
    }

    public function getMedia(string $collection = 'default'): Collection
    {
        return $this->media()
            ->wherePivot('collection', $collection)
            ->orderBy('mediables.sort_order')
            ->get();
    }

    public function getFirstMedia(string $collection = 'default'): ?Media
    {
        return $this->media()
            ->wherePivot('collection', $collection)
            ->orderBy('mediables.sort_order')
            ->first();
    }

    /**
     * @param  array<int>  $mediaIds
     */
    public function syncMedia(array $mediaIds, string $collection = 'default'): static
    {
        $mediaIds = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $mediaIds)));

        $this->media()->wherePivot('collection', $collection)->detach();

        if ($mediaIds === []) {
            return $this;
        }

        $syncPayload = [];
        foreach ($mediaIds as $index => $mediaId) {
            $syncPayload[$mediaId] = [
                'collection' => $collection,
                'sort_order' => $index + 1,
            ];
        }

        $this->media()->syncWithoutDetaching($syncPayload);

        return $this;
    }
}
