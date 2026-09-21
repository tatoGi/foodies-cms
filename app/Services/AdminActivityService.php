<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminActivityService
{
    /**
     * @return Collection<int, array{
     *     type:string,
     *     title:string,
     *     time:\Illuminate\Support\Carbon,
     *     desc:string,
     *     icon_bg:string,
     *     badge:string,
     *     badge_class:string
     * }>
     */
    public function recent(int $limit = 5): Collection
    {
        return $this->buildActivityCollection()
            ->sortByDesc('time')
            ->take($limit)
            ->values();
    }

    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $sorted = $this->buildActivityCollection()
            ->sortByDesc('time')
            ->values();

        $items = $sorted
            ->forPage($page, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * @return Collection<int, array{
     *     type:string,
     *     title:string,
     *     time:\Illuminate\Support\Carbon,
     *     desc:string,
     *     icon_bg:string,
     *     badge:string,
     *     badge_class:string
     * }>
     */
    private function buildActivityCollection(): Collection
    {
        $recentPages = Page::query()
            ->with('translations')
            ->latest('updated_at')
            ->take(200)
            ->get()
            ->map(function ($page): array {
                return [
                    'type' => 'page',
                    'title' => sprintf('%s "%s"', __('Updated page'), $page->translations->first()?->title ?? '#'.$page->id),
                    'time' => $page->updated_at,
                    'desc' => __('Modified on').' '.$page->updated_at->format('Y-m-d H:i'),
                    'icon_bg' => 'bg-primary',
                    'badge' => __('CMS Content'),
                    'badge_class' => 'badge-primary',
                ];
            });

        $recentPosts = Post::query()
            ->with('translations')
            ->latest('updated_at')
            ->take(200)
            ->get()
            ->map(function ($post): array {
                return [
                    'type' => 'post',
                    'title' => sprintf('%s "%s"', __('Published post'), $post->translations->first()?->title ?? '#'.$post->id),
                    'time' => $post->updated_at,
                    'desc' => __('Live on website since').' '.$post->updated_at->format('Y-m-d H:i'),
                    'icon_bg' => 'bg-success',
                    'badge' => __('Post'),
                    'badge_class' => 'badge-success',
                ];
            });

        $recentMedia = Media::query()
            ->latest('created_at')
            ->take(200)
            ->get()
            ->map(function ($media): array {
                return [
                    'type' => 'media',
                    'title' => sprintf('%s "%s"', __('Uploaded file'), $media->name),
                    'time' => $media->created_at,
                    'desc' => __('Added to library'),
                    'icon_bg' => 'bg-info',
                    'badge' => __('Media'),
                    'badge_class' => 'badge-info',
                ];
            });

        return collect($recentPages)
            ->concat($recentPosts)
            ->concat($recentMedia);
    }
}
