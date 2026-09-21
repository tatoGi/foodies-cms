<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Repositories\Contracts\WebsiteMenuRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentWebsiteMenuRepository implements WebsiteMenuRepositoryInterface
{
    /**
     * @return array<int, array{label:string,url:string,target:string,type:string,slug:?string,api_url:?string}>
     */
    /**
     * @return array<int, array{label:string,url:string,target:string,type:string,slug:?string,api_url:?string,children:array}>
     */
    public function resolvedItems(string $menuSlug, string $locale, string $fallbackLocale): array
    {
        if (! $this->menuTablesAvailable()) {
            return [];
        }

        $menu = Menu::query()
            ->where('slug', $menuSlug)
            ->where('is_active', true)
            ->first();

        if (! $menu instanceof Menu) {
            return [];
        }

        $allItems = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->with(['translations'])
            ->get();

        $pageMap = $this->dataMapForReferences($allItems, 'page', 'reference_id', 'page_translations', 'page_id', $locale, $fallbackLocale);
        $postMap = $this->dataMapForReferences($allItems, 'post', 'reference_id', 'post_translations', 'post_id', $locale, $fallbackLocale);
        $productMap = $this->productMapForReferences($allItems, $locale, $fallbackLocale);

        return $this->buildTree($allItems, null, $locale, $fallbackLocale, $pageMap, $postMap, $productMap);
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @param  array<int, array{slug:string, title:string}>  $pageMap
     * @param  array<int, array{slug:string, title:string}>  $postMap
     * @param  array<int, array{slug:string, title:string}>  $productMap
     */
    private function buildTree(
        Collection $allItems,
        ?int $parentId,
        string $locale,
        string $fallbackLocale,
        array $pageMap,
        array $postMap,
        array $productMap
    ): array {
        return $allItems
            ->where('parent_id', $parentId)
            ->sortBy('order')
            ->map(function (MenuItem $item) use ($allItems, $locale, $fallbackLocale, $pageMap, $postMap, $productMap): ?array {
                $resolvedData = $this->resolveItemData($item, $locale, $fallbackLocale, $pageMap, $postMap, $productMap);

                if ($resolvedData['url'] === '') {
                    return null;
                }

                $children = $this->buildTree($allItems, $item->id, $locale, $fallbackLocale, $pageMap, $postMap, $productMap);

                return [
                    'label' => $resolvedData['label'],
                    'url' => $resolvedData['url'],
                    'target' => in_array((string) $item->target, ['_self', '_blank'], true)
                        ? (string) $item->target
                        : '_self',
                    'type' => $resolvedData['type'],
                    'slug' => $resolvedData['slug'],
                    'api_url' => $resolvedData['api_url'],
                    'children' => $children,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveItemData(
        MenuItem $item,
        string $locale,
        string $fallbackLocale,
        array $pageMap,
        array $postMap,
        array $productMap
    ): array {
        $referenceId = $item->reference_id !== null ? (int) $item->reference_id : null;
        $label = '';
        $url = '';
        $type = 'custom';
        $slug = null;
        $apiUrl = null;

        if ($item->type === 'page' && $referenceId !== null && isset($pageMap[$referenceId])) {
            $label = $pageMap[$referenceId]['title'];
            $slug = $pageMap[$referenceId]['slug'];
            $isHome = (bool) ($pageMap[$referenceId]['is_home'] ?? false);
            $url = $this->normalizeUrl($isHome ? '/' : '/'.$slug);
            $type = 'page';
            $apiUrl = $isHome ? '/api/web/home' : '/api/web/pages/'.$slug;
        } elseif ($item->type === 'page' && $referenceId !== null) {
            // page exists in menu but is not published — hide it
            return ['label' => '', 'url' => '', 'type' => 'page', 'slug' => null, 'api_url' => null];
        } elseif ($item->type === 'post' && $referenceId !== null && isset($postMap[$referenceId])) {
            $label = $postMap[$referenceId]['title'];
            $slug = $postMap[$referenceId]['slug'];
            $url = $this->normalizeUrl('/blog/'.$slug);
            $type = 'post';
            $apiUrl = '/api/web/blog/'.$slug;
        } elseif ($item->type === 'product' && $referenceId !== null && isset($productMap[$referenceId])) {
            $label = $productMap[$referenceId]['title'];
            $slug = $productMap[$referenceId]['slug'];
            $url = $this->normalizeUrl('/products/'.$slug);
            $type = 'product';
            $apiUrl = '/api/web/products/'.$slug;
        } else {
            // Custom or fallback
            $label = $this->getMenuLabel($item, $locale, $fallbackLocale);
            $url = $this->normalizeUrl((string) ($item->url ?? ''));
        }

        return [
            'label' => $label ?: $url,
            'url' => $url,
            'type' => $type,
            'slug' => $slug,
            'api_url' => $apiUrl,
        ];
    }

    private function getMenuLabel(MenuItem $item, string $locale, string $fallbackLocale): string
    {
        $label = trim((string) ($item->translations->firstWhere('locale', $locale)?->label ?? ''));
        if ($label === '') {
            $label = trim((string) ($item->translations->firstWhere('locale', $fallbackLocale)?->label ?? ''));
        }
        if ($label === '') {
            $label = trim((string) ($item->translations->first()?->label ?? ''));
        }

        return $label;
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @return array<int, array{slug:string, title:string}>
     */
    private function dataMapForReferences(
        Collection $items,
        string $type,
        string $referenceKey,
        string $table,
        string $idColumn,
        string $locale,
        string $fallbackLocale
    ): array {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $ids = $items
            ->where('type', $type)
            ->pluck($referenceKey)
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $query = DB::table($table)
            ->whereIn($idColumn, $ids)
            ->whereIn('locale', [$locale, $fallbackLocale]);

        $columns = [$idColumn, 'locale', 'slug', 'title'];

        if ($type === 'page') {
            $query->join('pages', 'pages.id', '=', $table.'.'.$idColumn)
                ->where('pages.published', true);
            $columns[] = 'pages.is_home';
        }

        $rows = $query->get($columns);

        $mapped = [];
        foreach ($rows as $row) {
            $id = (int) ($row->{$idColumn} ?? 0);
            if ($id <= 0) {
                continue;
            }

            $data = [
                'slug' => trim((string) ($row->slug ?? '')),
                'title' => trim((string) ($row->title ?? '')),
                'is_home' => isset($row->is_home) ? (bool) $row->is_home : false,
            ];

            if ((string) $row->locale === $locale) {
                $mapped[$id] = $data;
            } elseif (! isset($mapped[$id])) {
                $mapped[$id] = $data;
            }
        }

        return $mapped;
    }

    private function productMapForReferences(Collection $items, string $locale, string $fallbackLocale): array
    {
        $table = 'product_translations';
        $idColumn = 'product_id';

        if (! Schema::hasTable($table)) {
            return [];
        }

        $ids = $items
            ->where('type', 'product')
            ->pluck('reference_id')
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $rows = DB::table($table)
            ->join('products', 'products.id', '=', $table.'.'.$idColumn)
            ->where('products.is_active', true)
            ->whereIn($table.'.'.$idColumn, $ids)
            ->whereIn($table.'.locale', [$locale, $fallbackLocale])
            ->get([$table.'.'.$idColumn, $table.'.locale', $table.'.slug', $table.'.title']);

        $mapped = [];
        foreach ($rows as $row) {
            $id = (int) ($row->{$idColumn} ?? 0);
            if ($id <= 0) {
                continue;
            }

            $data = [
                'slug' => trim((string) ($row->slug ?? '')),
                'title' => trim((string) ($row->title ?? '')),
            ];

            if ((string) $row->locale === $locale) {
                $mapped[$id] = $data;
            } elseif (! isset($mapped[$id])) {
                $mapped[$id] = $data;
            }
        }

        return $mapped;
    }

    private function normalizeUrl(string $url): string
    {
        $value = trim($url);
        if ($value === '') {
            return '';
        }

        if (
            str_starts_with($value, '/') ||
            str_starts_with($value, '#') ||
            str_starts_with($value, 'http://') ||
            str_starts_with($value, 'https://') ||
            str_starts_with($value, 'mailto:') ||
            str_starts_with($value, 'tel:')
        ) {
            return $value;
        }

        return '/'.ltrim($value, '/');
    }

    private function menuTablesAvailable(): bool
    {
        return Schema::hasTable('menus')
            && Schema::hasTable('menu_items')
            && Schema::hasTable('menu_item_translations');
    }
}
