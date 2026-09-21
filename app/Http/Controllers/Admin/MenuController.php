<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMenuRequest;
use App\Http\Requests\Admin\UpdateMenuRequest;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Services\MenuPageSlugSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(
        private readonly MenuPageSlugSyncService $menuPageSlugSyncService,
    ) {}

    public function index(): View
    {
        $menus = Menu::query()
            ->withCount('items')
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return view('admin.menus.index', [
            'menus' => $menus,
        ]);
    }

    public function create(): View
    {
        $locales = $this->locales();
        $defaultLocale = $this->defaultLocaleCode($locales);

        return view('admin.menus.create', [
            'menu' => null,
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'items' => $this->itemsFromOldInput([]),
            'sources' => $this->menuSources($defaultLocale),
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $menu = Menu::query()->create([
                'title' => trim((string) $request->input('title')),
                'slug' => trim((string) $request->input('slug')),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncMenuItems($menu, (array) $request->input('items', []));
        });

        return redirect()->route('admin.menus.index')
            ->with('success', __('Menu created successfully.'));
    }

    public function edit(Menu $menu): View
    {
        $menu->load(['items.translations']);

        $locales = $this->locales();
        $defaultLocale = $this->defaultLocaleCode($locales);

        return view('admin.menus.edit', [
            'menu' => $menu,
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'items' => $this->itemsFromOldInput($this->formatMenuItems($menu, $defaultLocale)),
            'sources' => $this->menuSources($defaultLocale),
        ]);
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        DB::transaction(function () use ($request, $menu): void {
            $menu->update([
                'title' => trim((string) $request->input('title')),
                'slug' => trim((string) $request->input('slug')),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncMenuItems($menu, (array) $request->input('items', []));
        });

        return redirect()->route('admin.menus.index')
            ->with('success', __('Menu updated successfully.'));
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')
            ->with('success', __('Menu deleted successfully.'));
    }

    /**
     * @return array<int, array{id:int,title:string,slug:string,url:string,labels:array<string,string>,parent_id:?int,depth:int,children:array<int, array<string, mixed>>}>
     */
    private function pageSources(string $defaultLocale): array
    {
        $pages = Page::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sourcesById = $pages
            ->mapWithKeys(function (Page $page) use ($defaultLocale): array {
                $labels = $this->translationMap($page->translations, 'title');
                $slugs = $this->translationMap($page->translations, 'slug');
                $slug = $slugs[$defaultLocale] ?? collect($slugs)->first() ?? '';

                return [(int) $page->id => [
                    'id' => (int) $page->id,
                    'title' => (string) ($labels[$defaultLocale] ?? collect($labels)->first() ?? '#'.$page->id),
                    'slug' => (string) $slug,
                    'url' => $slug !== '' ? '/'.ltrim($slug, '/') : '',
                    'labels' => $labels,
                    'parent_id' => $page->parent_id !== null ? (int) $page->parent_id : null,
                ]];
            })
            ->all();

        $childrenByParent = [];
        foreach ($pages as $page) {
            $parentId = $page->parent_id !== null ? (int) $page->parent_id : 0;
            $childrenByParent[$parentId] ??= [];
            $childrenByParent[$parentId][] = (int) $page->id;
        }

        $buildNode = function (int $pageId, int $depth = 0, array $ancestors = []) use (&$buildNode, $sourcesById, $childrenByParent): ?array {
            if (isset($ancestors[$pageId]) || ! isset($sourcesById[$pageId])) {
                return null;
            }

            $node = $sourcesById[$pageId];
            $node['depth'] = $depth;
            $node['children'] = [];
            $ancestors[$pageId] = true;

            foreach ($childrenByParent[$pageId] ?? [] as $childId) {
                $childNode = $buildNode($childId, $depth + 1, $ancestors);
                if (is_array($childNode)) {
                    $node['children'][] = $childNode;
                }
            }

            return $node;
        };

        $tree = [];

        foreach ($childrenByParent[0] ?? [] as $rootId) {
            $node = $buildNode($rootId);
            if (is_array($node)) {
                $tree[] = $node;
            }
        }

        $rendered = collect($this->flattenPageSourceTree($tree))
            ->pluck('id')
            ->mapWithKeys(static fn ($id): array => [(int) $id => true])
            ->all();

        foreach (array_keys($sourcesById) as $pageId) {
            if (isset($rendered[$pageId])) {
                continue;
            }

            $node = $buildNode((int) $pageId);
            if (is_array($node)) {
                $tree[] = $node;
            }
        }

        return $this->flattenPageSourceTree($tree);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @return array<int, array<string, mixed>>
     */
    private function flattenPageSourceTree(array $tree): array
    {
        $flattened = [];

        $walk = function (array $nodes) use (&$walk, &$flattened): void {
            foreach ($nodes as $node) {
                $flattened[] = $node;

                $children = $node['children'] ?? [];
                if (is_array($children) && $children !== []) {
                    $walk($children);
                }
            }
        };

        $walk($tree);

        return $flattened;
    }

    /**
     * @return array<int, array{id:int,title:string,slug:string,url:string,labels:array<string,string>}>
     */
    private function postSources(string $defaultLocale): array
    {
        return Post::query()
            ->with('translations')
            ->orderByDesc('id')
            ->get()
            ->map(function (Post $post) use ($defaultLocale): array {
                $labels = $this->translationMap($post->translations, 'title');
                $slugs = $this->translationMap($post->translations, 'slug');
                $slug = $slugs[$defaultLocale] ?? collect($slugs)->first() ?? '';

                return [
                    'id' => (int) $post->id,
                    'title' => (string) ($labels[$defaultLocale] ?? collect($labels)->first() ?? '#'.$post->id),
                    'slug' => (string) $slug,
                    'url' => $slug !== '' ? '/blog/'.ltrim($slug, '/') : '',
                    'labels' => $labels,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,title:string,slug:string,url:string,labels:array<string,string>}>
     */
    private function productSources(string $defaultLocale): array
    {
        return Product::query()
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(function (Product $product) use ($defaultLocale): array {
                $labels = $this->translationMap($product->translations, 'title');
                $slugs = $this->translationMap($product->translations, 'slug');
                $slug = $slugs[$defaultLocale] ?? collect($slugs)->first() ?? '';

                return [
                    'id' => (int) $product->id,
                    'title' => (string) ($labels[$defaultLocale] ?? collect($labels)->first() ?? '#'.$product->id),
                    'slug' => (string) $slug,
                    'url' => $slug !== '' ? '/products/'.ltrim($slug, '/') : '',
                    'labels' => $labels,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{pages:array<int, array<string, mixed>>,posts:array<int, array<string, mixed>>,products:array<int, array<string, mixed>>}
     */
    private function menuSources(string $defaultLocale): array
    {
        return [
            'pages' => $this->pageSources($defaultLocale),
            'posts' => $this->postSources($defaultLocale),
            'products' => $this->productSources($defaultLocale),
            'programs' => [],
        ];
    }

    /**
     * @param  Collection<int, mixed>  $translations
     * @return array<string, string>
     */
    private function translationMap(Collection $translations, string $field): array
    {
        return $translations
            ->mapWithKeys(function ($translation) use ($field): array {
                $locale = trim((string) ($translation->locale ?? ''));
                if ($locale === '') {
                    return [];
                }

                return [$locale => trim((string) ($translation->{$field} ?? ''))];
            })
            ->filter(static fn (string $value): bool => $value !== '')
            ->all();
    }

    /**
     * @return array<int, array{code:string,name:string,flag:string,is_default:bool}>
     */
    private function locales(): array
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('english_name')
            ->get(['code', 'name', 'country_code', 'is_default']);

        if ($languages->isEmpty()) {
            $fallbackCode = app()->getLocale();

            return [[
                'code' => $fallbackCode,
                'name' => strtoupper($fallbackCode),
                'flag' => '',
                'is_default' => true,
            ]];
        }

        return $languages
            ->map(static function (Language $language): array {
                $code = (string) $language->code;

                return [
                    'code' => $code,
                    'name' => (string) ($language->name ?: strtoupper($code)),
                    'flag' => (string) $language->country_code,
                    'is_default' => (bool) $language->is_default,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{code:string,name:string,flag:string,is_default:bool}>  $locales
     */
    private function defaultLocaleCode(array $locales): string
    {
        $defaultLocale = collect($locales)->firstWhere('is_default', true);
        if (is_array($defaultLocale) && isset($defaultLocale['code'])) {
            return (string) $defaultLocale['code'];
        }

        return (string) (collect($locales)->first()['code'] ?? app()->getLocale());
    }

    /**
     * @return array<int, array{key:string,id:int,parent_key:string,type:string,reference_id:?int,url:string,target:string,order:int,labels:array<string,string>}>
     */
    private function formatMenuItems(Menu $menu, string $defaultLocale): array
    {
        $items = $menu->items
            ->sortBy('order')
            ->values();

        $keysById = [];
        foreach ($items as $item) {
            $keysById[(int) $item->id] = 'item_'.(int) $item->id;
        }

        return $items
            ->map(function (MenuItem $item) use ($keysById, $defaultLocale): array {
                $labels = $item->translations
                    ->mapWithKeys(static fn ($translation): array => [
                        (string) $translation->locale => (string) $translation->label,
                    ])
                    ->all();

                return [
                    'key' => 'item_'.(int) $item->id,
                    'id' => (int) $item->id,
                    'parent_key' => $item->parent_id !== null ? ($keysById[(int) $item->parent_id] ?? '') : '',
                    'type' => (string) $item->type,
                    'reference_id' => $item->reference_id !== null ? (int) $item->reference_id : null,
                    'url' => (string) ($item->url ?? ''),
                    'target' => (string) ($item->target ?: '_self'),
                    'order' => (int) $item->order,
                    'labels' => $labels !== [] ? $labels : [$defaultLocale => (string) ($item->url ?? '')],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $rawItems
     */
    private function syncMenuItems(Menu $menu, array $rawItems): void
    {
        $existingItems = $menu->items()->with('translations')->get()->keyBy('id');
        $normalizedItems = collect($rawItems)
            ->map(function ($payload, $key): array {
                $data = (array) $payload;

                return [
                    'key' => trim((string) $key),
                    'id' => isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null,
                    'parent_key' => trim((string) ($data['parent_key'] ?? '')),
                    'type' => trim((string) ($data['type'] ?? 'custom')),
                    'reference_id' => isset($data['reference_id']) && $data['reference_id'] !== '' ? (int) $data['reference_id'] : null,
                    'url' => trim((string) ($data['url'] ?? '')),
                    'target' => in_array((string) ($data['target'] ?? '_self'), ['_self', '_blank'], true) ? (string) $data['target'] : '_self',
                    'labels' => collect((array) ($data['labels'] ?? []))
                        ->map(static fn ($label): string => trim((string) $label))
                        ->filter(static fn (string $label): bool => $label !== '')
                        ->all(),
                    'order' => (int) ($data['order'] ?? 0),
                ];
            })
            ->filter(static fn (array $item): bool => $item['key'] !== '')
            ->values();

        $normalizedItems = $this->expandNewPageItemsWithDescendants($normalizedItems);

        $incomingExistingIds = $normalizedItems
            ->pluck('id')
            ->filter(static fn ($id): bool => is_int($id) && $id > 0)
            ->all();

        $menu->items()
            ->whereNotIn('id', $incomingExistingIds === [] ? [0] : $incomingExistingIds)
            ->delete();

        $pageSources = collect($this->pageSources(app()->getLocale()))->keyBy('id');
        $postSources = collect($this->postSources(app()->getLocale()))->keyBy('id');
        $productSources = collect($this->productSources(app()->getLocale()))->keyBy('id');
        $resolvedIdsByKey = [];
        $resolvedItems = [];
        $referencedPageIds = [];

        foreach ($normalizedItems as $index => $item) {
            $menuItem = null;
            if ($item['id'] !== null) {
                $menuItem = $existingItems->get($item['id']);
            }

            if (! $menuItem instanceof MenuItem) {
                $menuItem = new MenuItem;
                $menuItem->menu_id = (int) $menu->id;
            }

            $source = null;
            if ($item['type'] === 'page' && $item['reference_id'] !== null) {
                $source = $pageSources->get($item['reference_id']);
                $referencedPageIds[] = (int) $item['reference_id'];
            } elseif ($item['type'] === 'post' && $item['reference_id'] !== null) {
                $source = $postSources->get($item['reference_id']);
            } elseif ($item['type'] === 'product' && $item['reference_id'] !== null) {
                $source = $productSources->get($item['reference_id']);
            }

            $resolvedUrl = $item['type'] === 'page'
                ? (is_array($source) ? (string) ($source['url'] ?? '') : '')
                : (string) ($source['url'] ?? $item['url']);
            $menuItem->fill([
                'type' => in_array($item['type'], ['custom', 'page', 'post', 'program', 'product'], true) ? $item['type'] : 'custom',
                'reference_id' => $item['type'] === 'custom' ? null : $item['reference_id'],
                'url' => $resolvedUrl !== '' ? $resolvedUrl : null,
                'target' => $item['target'],
                'order' => $index,
                'parent_id' => null,
            ]);
            $menuItem->save();

            $resolvedIdsByKey[$item['key']] = (int) $menuItem->id;
            $resolvedItems[] = [
                ...$item,
                'model' => $menuItem,
                'source' => is_array($source) ? $source : null,
            ];
        }

        foreach ($resolvedItems as $item) {
            /** @var MenuItem $model */
            $model = $item['model'];
            $parentId = null;
            if ($item['parent_key'] !== '' && isset($resolvedIdsByKey[$item['parent_key']])) {
                $candidate = (int) $resolvedIdsByKey[$item['parent_key']];
                if ($candidate !== (int) $model->id) {
                    $parentId = $candidate;
                }
            }

            $model->update(['parent_id' => $parentId]);

            $labels = (array) $item['labels'];
            $sourceLabels = is_array($item['source']) ? (array) ($item['source']['labels'] ?? []) : [];
            $finalLabels = $item['type'] === 'page'
                ? $sourceLabels
                : ($labels !== [] ? $labels : $sourceLabels);
            if ($finalLabels === []) {
                $fallbackLabel = $model->url ?: Str::title($model->type).' #'.$model->id;
                $finalLabels = [app()->getLocale() => $fallbackLabel];
            }

            $model->translations()->delete();
            foreach ($finalLabels as $locale => $label) {
                $locale = trim((string) $locale);
                $label = trim((string) $label);
                if ($locale === '' || $label === '') {
                    continue;
                }

                $model->translations()->create([
                    'locale' => $locale,
                    'label' => $label,
                    'slug' => Str::slug($label) !== '' ? Str::slug($label) : 'menu-item-'.$model->id.'-'.$locale,
                ]);
            }
        }

        foreach (array_values(array_unique($referencedPageIds)) as $pageId) {
            $this->menuPageSlugSyncService->syncMenuItemsFromPage((int) $pageId);
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function expandNewPageItemsWithDescendants(Collection $items): Collection
    {
        $existingPageRefs = $items
            ->filter(static fn (array $item): bool => $item['type'] === 'page' && $item['reference_id'] !== null)
            ->pluck('reference_id')
            ->mapWithKeys(static fn ($id): array => [(int) $id => true])
            ->all();

        $autoCounter = 0;
        $expanded = [];

        foreach ($items as $item) {
            $expanded[] = $item;

            $isFreshPage = $item['id'] === null
                && $item['type'] === 'page'
                && $item['reference_id'] !== null;

            if ($isFreshPage) {
                $this->autoExpandPageChildren(
                    (int) $item['reference_id'],
                    $item['key'],
                    $expanded,
                    $existingPageRefs,
                    $autoCounter,
                );
            }
        }

        return collect($expanded)->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $expanded
     * @param  array<int, true>  $existingPageRefs
     */
    private function autoExpandPageChildren(
        int $parentPageId,
        string $parentKey,
        array &$expanded,
        array &$existingPageRefs,
        int &$autoCounter,
    ): void {
        $children = Page::query()
            ->where('parent_id', $parentPageId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        foreach ($children as $child) {
            $childId = (int) $child->id;

            if (isset($existingPageRefs[$childId])) {
                continue;
            }

            $autoCounter++;
            $childKey = 'auto_page_'.$childId.'_'.$autoCounter;
            $existingPageRefs[$childId] = true;

            $expanded[] = [
                'key' => $childKey,
                'id' => null,
                'parent_key' => $parentKey,
                'type' => 'page',
                'reference_id' => $childId,
                'url' => '',
                'target' => '_self',
                'labels' => [],
                'order' => count($expanded),
            ];

            $this->autoExpandPageChildren($childId, $childKey, $expanded, $existingPageRefs, $autoCounter);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $fallback
     * @return array<int, array<string, mixed>>
     */
    private function itemsFromOldInput(array $fallback): array
    {
        $oldItems = old('items');
        if (! is_array($oldItems)) {
            return $fallback;
        }

        return collect($oldItems)
            ->map(function ($payload, $key): array {
                $item = (array) $payload;

                return [
                    'key' => (string) $key,
                    'id' => isset($item['id']) && $item['id'] !== '' ? (int) $item['id'] : null,
                    'parent_key' => trim((string) ($item['parent_key'] ?? '')),
                    'type' => trim((string) ($item['type'] ?? 'custom')),
                    'reference_id' => isset($item['reference_id']) && $item['reference_id'] !== '' ? (int) $item['reference_id'] : null,
                    'url' => trim((string) ($item['url'] ?? '')),
                    'target' => trim((string) ($item['target'] ?? '_self')),
                    'order' => (int) ($item['order'] ?? 0),
                    'labels' => collect((array) ($item['labels'] ?? []))
                        ->map(static fn ($label): string => trim((string) $label))
                        ->filter(static fn (string $label): bool => $label !== '')
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
