<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Page;
use App\Models\PageTemplate;
use App\Repositories\Contracts\PageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PageRepository implements PageRepositoryInterface
{
    public function paginateWithTranslationsAndTemplate(int $perPage = 15): LengthAwarePaginator
    {
        return Page::query()
            ->with(['translations', 'templateRef.translations'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<int, array{id:int,title:string}>
     */
    public function allExcept(?int $exceptId = null): array
    {
        $currentLocale = app()->getLocale();

        return Page::query()
            ->when($exceptId !== null, static fn ($query) => $query->where('id', '!=', $exceptId))
            ->with('translations')
            ->orderBy('id')
            ->get()
            ->map(static function (Page $page) use ($currentLocale): array {
                $preferred = $page->translations->firstWhere('locale', $currentLocale);
                $fallback = $page->translations->first();

                return [
                    'id' => (int) $page->id,
                    'title' => (string) ($preferred?->title ?? $fallback?->title ?? '#'.$page->id),
                ];
            })
            ->values()
            ->all();
    }

    public function create(array $data): Page
    {
        return Page::query()->create($data);
    }

    public function update(Page $page, array $data): Page
    {
        $page->update($data);

        return $page;
    }

    public function clearHomeFlagExcept(?int $exceptPageId = null): void
    {
        Page::query()
            ->when($exceptPageId !== null, static fn ($query) => $query->where('id', '!=', $exceptPageId))
            ->where('is_home', true)
            ->update(['is_home' => false]);
    }

    public function delete(Page $page): bool
    {
        return (bool) $page->delete();
    }

    public function reorderByIds(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                Page::query()->where('id', (int) $id)->update(['sort_order' => $position]);
            }
        });
    }

    /**
     * @return Collection<int, Page>
     */
    public function allWithTranslationsAndTemplateOrdered(): Collection
    {
        return Page::query()
            ->with(['translations', 'templateRef.translations'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    public function allIds(): array
    {
        return Page::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, array{id:int,parent_id:?int,sort_order:int}>  $updates
     */
    public function updateHierarchy(array $updates): void
    {
        DB::transaction(function () use ($updates): void {
            foreach ($updates as $update) {
                Page::query()
                    ->where('id', (int) $update['id'])
                    ->update([
                        'parent_id' => $update['parent_id'],
                        'sort_order' => (int) $update['sort_order'],
                    ]);
            }
        });
    }

    /**
     * @return array<int, array{slug:string,name:string}>
     */
    public function allTemplates(): array
    {
        $currentLocale = app()->getLocale();

        return PageTemplate::query()
            ->with('translations')
            ->orderBy('slug')
            ->get()
            ->map(static function (PageTemplate $template) use ($currentLocale): array {
                $preferred = $template->translations->firstWhere('locale', $currentLocale);
                $fallback = $template->translations->first();

                return [
                    'slug' => (string) $template->slug,
                    'name' => (string) ($preferred?->name ?? $fallback?->name ?? $template->slug),
                ];
            })
            ->values()
            ->all();
    }
}
