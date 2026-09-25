<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\BlockTypeDefinition;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTranslation;
use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Website\RevalidateFrontendService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly BlockTypeRepositoryInterface $blockTypeRepository,
        private readonly LanguageRepositoryInterface $languageRepository,
        private readonly BlockNormalizationService $blockNormalizationService,
        private readonly RevalidateFrontendService $frontend,
    ) {}

    /**
     * @return array{
     *     products: LengthAwarePaginator,
     *     categoryTabs: list<array{key: string, name: string, count: int}>,
     *     activeCategory: string,
     *     currentLocale: string,
     *     search: string
     * }
     */
    public function buildIndexViewData(string $search = '', string $category = ''): array
    {
        $locale = app()->getLocale();
        $tabs = $this->categoryTabs($search, $locale);
        $active = $this->activeCategory($category, $tabs);

        return [
            'products' => $this->productRepository->paginateWithTranslations(10, $search, $active),
            'categoryTabs' => $tabs,
            'activeCategory' => $active,
            'currentLocale' => $locale,
            'search' => $search,
        ];
    }

    /**
     * @return list<array{key: string, name: string, count: int}>
     */
    private function categoryTabs(string $search, string $locale): array
    {
        $categories = $this->productRepository->categoriesWithProductCounts($search, $locale);
        $uncategorized = $this->productRepository->countWithoutCategory($search);
        $tabs = [[
            'key' => 'all',
            'name' => 'ყველა',
            'count' => $uncategorized + array_sum(array_column($categories, 'count')),
        ]];

        foreach ($categories as $category) {
            $tabs[] = [
                'key' => (string) $category['id'],
                'name' => $category['name'],
                'count' => $category['count'],
            ];
        }

        if ($uncategorized > 0) {
            $tabs[] = [
                'key' => 'none',
                'name' => 'უსათაურო',
                'count' => $uncategorized,
            ];
        }

        return $tabs;
    }

    /**
     * @param  list<array{key: string, name: string, count: int}>  $tabs
     */
    private function activeCategory(string $requested, array $tabs): string
    {
        $keys = array_column($tabs, 'key');
        if ($requested !== '' && in_array($requested, $keys, true)) {
            return $requested;
        }

        foreach ($tabs as $tab) {
            if ($tab['key'] !== 'all') {
                return $tab['key'];
            }
        }

        return 'all';
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function productCategoryOptions(): array
    {
        $locale = app()->getLocale();

        return ProductCategory::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhereHas('products'))
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(static function (ProductCategory $category) use ($locale): array {
                $translation = $category->translations->firstWhere('locale', $locale)
                    ?? $category->translations->firstWhere('locale', 'ka')
                    ?? $category->translations->first();

                return [
                    'id' => $category->id,
                    'name' => $translation?->name ?? '#'.$category->id,
                ];
            })
            ->all();
    }

    /** @return array<string, mixed> */
    public function buildCreateViewData(): array
    {
        $locales = $this->languageRepository->getActiveLocales();
        $localeCodes = collect($locales)->pluck('code')->map(static fn ($c): string => (string) $c)->values()->all();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $selectedLocaleCodes = $this->selectedLocaleCodesFromOldInput($locales, $localeCodes);
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('product');
        $selectedBlockTypes = $this->normalizeSelectedBlockTypes((array) old('block_types', []), $blockDefinitions);
        $blockTypeOptions = $this->formatBlockTypeOptions($blockDefinitions);
        $blockTypeEditors = $this->formatBlockTypeEditors($blockDefinitions, $localeCodes);
        $localeBlocks = $this->resolveLocaleBlocks($localeCodes, $selectedBlockTypes, $blockTypeEditors, null);
        $selectedPageIds = $this->selectedPageIdsFromOldInput([]);

        return [
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
            'blockTypes' => $blockTypeOptions,
            'blockTypeEditors' => $blockTypeEditors,
            'selectedBlockTypes' => $selectedBlockTypes,
            'localeBlocks' => $localeBlocks,
            'availablePages' => $this->availablePages(),
            'selectedPageIds' => $selectedPageIds,
            'productCategories' => $this->productCategoryOptions(),
        ];
    }

    /** @return array<string, mixed> */
    public function buildEditViewData(Product $product): array
    {
        $product->load(['translations.blocks', 'ingredients', 'addons', 'productCategory.translations']);

        $locales = $this->languageRepository->getActiveLocales();
        $localeCodes = collect($locales)->pluck('code')->map(static fn ($c): string => (string) $c)->values()->all();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $fallbackSelected = $localeCodes;
        $selectedLocaleCodes = $this->selectedLocaleCodesFromOldInput($locales, $fallbackSelected);
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('product');
        $storedBlockTypes = $this->normalizeBlockTypes((array) ($product->block_types ?? []));
        if ($storedBlockTypes === []) {
            $storedBlockTypes = $this->resolveBlockTypesFromTranslations($product);
        }
        $selectedBlockTypes = $this->normalizeSelectedBlockTypes((array) old('block_types', $storedBlockTypes), $blockDefinitions);
        $blockTypeOptions = $this->formatBlockTypeOptions($blockDefinitions);
        $blockTypeEditors = $this->formatBlockTypeEditors($blockDefinitions, $localeCodes);
        $localeBlocks = $this->resolveLocaleBlocks($localeCodes, $selectedBlockTypes, $blockTypeEditors, $product);
        $selectedPageIds = $this->selectedPageIdsFromOldInput(
            $product->pages()->pluck('pages.id')->map(static fn ($id): int => (int) $id)->all()
        );

        return [
            'product' => $product,
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
            'translations' => $product->translations->keyBy('locale'),
            'blockTypes' => $blockTypeOptions,
            'blockTypeEditors' => $blockTypeEditors,
            'selectedBlockTypes' => $selectedBlockTypes,
            'localeBlocks' => $localeBlocks,
            'availablePages' => $this->availablePages(),
            'selectedPageIds' => $selectedPageIds,
            'productCategories' => $this->productCategoryOptions(),
        ];
    }

    public function create(StoreProductRequest $request): Product
    {
        $product = DB::transaction(function () use ($request): Product {
            $product = $this->productRepository->create([
                'sort_order' => $this->productRepository->nextSortOrder(),
                'sku' => trim((string) $request->input('sku', '')),
                'price' => (float) $request->input('price', 0),
                'product_category_id' => $request->filled('product_category_id') ? (int) $request->input('product_category_id') : null,
                'stock' => 0,
                'is_active' => $request->boolean('is_active', true),
                'cover_image' => trim((string) $request->input('cover_image', '')) ?: null,
                'block_types' => $this->normalizeBlockTypes((array) $request->input('block_types', [])),
                'is_featured' => $request->boolean('is_featured'),
                'published' => $request->boolean('published'),
                'published_at' => $request->input('published_at') ?: null,
            ]);

            $this->syncTranslations(
                $product,
                (array) $request->input('names', []),
                (array) $request->input('categories', []),
                (array) $request->input('slugs', []),
                (array) $request->input('descriptions', []),
                (array) $request->input('meta_titles', []),
                (array) $request->input('meta_descriptions', []),
                (array) $request->input('keywords', []),
                (array) $request->input('focus_keywords', []),
                (array) $request->input('canonical_urls', []),
                (array) $request->input('blocks', []),
                (array) $request->file('blocks', []),
                (array) $request->input('excerpts', [])
            );
            $product->pages()->sync(
                $this->normalizeSelectedPageIds((array) $request->input('page_ids', []))
            );

            return $product;
        });
        $this->refreshFrontend($product);

        return $product;
    }

    public function update(UpdateProductRequest $request, Product $product): Product
    {
        $product = DB::transaction(function () use ($request, $product): Product {
            $fromPos = $product->isSyncedFromPos();
            $names = (array) $request->input('names', []);
            if ($fromPos) {
                foreach ($product->translations()->pluck('title', 'locale') as $locale => $title) {
                    $names[(string) $locale] = (string) $title;
                }
            }

            $attributes = [
                'sku' => $fromPos ? $product->sku : trim((string) $request->input('sku', '')),
                'price' => $fromPos ? $product->price : (float) $request->input('price', 0),
                'is_active' => $fromPos ? $product->is_active : $request->boolean('is_active', true),
                'cover_image' => $fromPos ? $product->cover_image : (trim((string) $request->input('cover_image', '')) ?: null),
                'block_types' => $this->normalizeBlockTypes((array) $request->input('block_types', [])),
                'is_featured' => $request->boolean('is_featured'),
                'published' => $request->boolean('published'),
                'published_at' => $request->input('published_at') ?: null,
            ];
            if (! $fromPos && $request->exists('product_category_id')) {
                $attributes['product_category_id'] = $request->filled('product_category_id')
                    ? (int) $request->input('product_category_id')
                    : null;
            }
            $this->productRepository->update($product, $attributes);

            $this->syncTranslations(
                $product,
                $names,
                (array) $request->input('categories', []),
                (array) $request->input('slugs', []),
                (array) $request->input('descriptions', []),
                (array) $request->input('meta_titles', []),
                (array) $request->input('meta_descriptions', []),
                (array) $request->input('keywords', []),
                (array) $request->input('focus_keywords', []),
                (array) $request->input('canonical_urls', []),
                (array) $request->input('blocks', []),
                (array) $request->file('blocks', []),
                (array) $request->input('excerpts', [])
            );
            $product->pages()->sync(
                $this->normalizeSelectedPageIds((array) $request->input('page_ids', []))
            );

            return $product;
        });
        $this->refreshFrontend($product);

        return $product;
    }

    public function delete(Product $product): void
    {
        $tags = $this->frontendTags($product);
        $this->productRepository->delete($product);
        $this->frontend->revalidate($tags);
    }

    public function reorder(array $orderedIds, int $page = 1, int $perPage = 15): void
    {
        $this->productRepository->reorderByIds($orderedIds, $page, $perPage);
        $this->frontend->revalidate(['menu', 'pages']);
    }

    /** Featured, published and ordering changes show on the home page (pages) and menu at once. */
    private function refreshFrontend(Product $product): void
    {
        $this->frontend->revalidate($this->frontendTags($product));
    }

    /** @return list<string> */
    private function frontendTags(Product $product): array
    {
        $slugs = $product->translations()->pluck('slug')
            ->filter(fn (mixed $slug): bool => is_string($slug) && $slug !== '')
            ->map(fn (string $slug): string => 'product:'.$slug)
            ->all();

        return array_values(array_merge(['menu', 'pages'], $slugs));
    }

    private function syncTranslations(
        Product $product,
        array $names,
        array $categories,
        array $slugs,
        array $descriptions,
        array $metaTitles,
        array $metaDescriptions,
        array $keywords,
        array $focusKeywords,
        array $canonicalUrls,
        array $blocksByLocale,
        array $blockFilesByLocale,
        array $excerpts = []
    ): void {
        $localeCodes = collect($this->languageRepository->getActiveLocales())->pluck('code')->map(static fn ($l): string => (string) $l)->values();
        $selectedBlockTypes = $this->normalizeBlockTypes((array) ($product->block_types ?? []));
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('product')
            ->keyBy(static fn (BlockTypeDefinition $bt): string => (string) $bt->key);

        $handledLocales = [];
        foreach ($localeCodes as $locale) {
            $name = trim((string) ($names[$locale] ?? ''));
            $category = trim((string) ($categories[$locale] ?? ''));
            $slug = trim((string) ($slugs[$locale] ?? ''));
            $content = trim((string) ($descriptions[$locale] ?? ''));
            $metaTitle = trim((string) ($metaTitles[$locale] ?? ''));
            $metaDesc = trim((string) ($metaDescriptions[$locale] ?? ''));
            $keyword = trim((string) ($keywords[$locale] ?? ''));
            $focusKeyword = trim((string) ($focusKeywords[$locale] ?? ''));
            $canonicalUrl = trim((string) ($canonicalUrls[$locale] ?? ''));
            $existing = $product->translations()->where('locale', $locale)->first();
            if ($category === '' && $existing instanceof ProductTranslation) {
                $category = (string) ($existing->category ?? '');
            }
            $excerpt = array_key_exists($locale, $excerpts)
                ? (trim((string) $excerpts[$locale]) ?: null)
                : $existing?->excerpt;

            if ($name !== '' || $slug !== '') {
                $translation = $product->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $name,
                        'category' => $category !== '' ? $category : null,
                        'slug' => $slug,
                        'excerpt' => $excerpt,
                        'content' => $content !== '' ? $content : null,
                        'meta_title' => $metaTitle !== '' ? $metaTitle : null,
                        'meta_description' => $metaDesc !== '' ? $metaDesc : null,
                        'keywords' => $keyword !== '' ? $keyword : null,
                        'focus_keyword' => $focusKeyword !== '' ? $focusKeyword : null,
                        'canonical_url' => $canonicalUrl !== '' ? $canonicalUrl : null,
                    ]
                );
                $this->syncTranslationBlocks(
                    $translation,
                    (array) ($blocksByLocale[$locale] ?? []),
                    (array) ($blockFilesByLocale[$locale] ?? []),
                    $selectedBlockTypes,
                    $blockDefinitions
                );

                $handledLocales[] = $locale;
            }
        }

        $product->translations()->whereNotIn('locale', $handledLocales)->delete();
    }

    /** @param Collection<string, BlockTypeDefinition> $blockDefinitions */
    private function syncTranslationBlocks(
        ProductTranslation $translation,
        array $localeBlocks,
        array $localeBlockFiles,
        array $selectedBlockTypes,
        Collection $blockDefinitions
    ): void {
        $existingBlocksByType = $translation->blocks
            ->groupBy(static fn ($block): string => (string) $block->type)
            ->map(static fn (Collection $items): Collection => $items->sortBy('sort_order')->values());
        $availableTypeCounts = array_count_values($selectedBlockTypes);
        $typeOffsets = [];

        $normalizedBlocks = collect($localeBlocks)
            ->map(function ($payload, $instanceKey) use (
                $localeBlockFiles,
                $blockDefinitions,
                $existingBlocksByType,
                &$availableTypeCounts,
                &$typeOffsets
            ): ?array {
                if (! is_array($payload)) {
                    return null;
                }

                $blockKey = trim((string) ($payload['type'] ?? $payload['key'] ?? ''));
                if ($blockKey === '' || ! isset($availableTypeCounts[$blockKey]) || $availableTypeCounts[$blockKey] < 1) {
                    return null;
                }

                $definition = $blockDefinitions->get($blockKey);
                if (! $definition instanceof BlockTypeDefinition) {
                    return null;
                }

                $availableTypeCounts[$blockKey]--;
                $instanceKey = trim((string) ($payload['instance_key'] ?? $instanceKey));
                $instanceFiles = $instanceKey !== '' ? (array) ($localeBlockFiles[$instanceKey] ?? []) : [];
                $typeOffsets[$blockKey] = ($typeOffsets[$blockKey] ?? 0);
                $existingBlock = $existingBlocksByType->get($blockKey)?->get($typeOffsets[$blockKey]);
                $typeOffsets[$blockKey]++;
                $existingData = is_object($existingBlock) ? (array) ($existingBlock->data ?? []) : [];

                return [
                    'type' => $blockKey,
                    'sort_order' => (int) ($payload['sort_order'] ?? 0),
                    'data' => $this->blockNormalizationService->normalizeBlockData(
                        (array) ($payload['data'] ?? []),
                        (array) ($payload['remove'] ?? []),
                        (array) ($payload['remove_items'] ?? []),
                        (array) ($instanceFiles['files'] ?? []),
                        $definition,
                        $existingData
                    ),
                ];
            })
            ->filter(static fn (?array $block): bool => $block !== null)
            ->sortBy('sort_order')
            ->values()
            ->all();

        $translation->blocks()->delete();
        foreach (collect($normalizedBlocks)->values() as $position => $block) {
            $translation->blocks()->create([
                'type' => (string) $block['type'],
                'data' => (array) $block['data'],
                'sort_order' => $position,
            ]);
        }
    }

    /** @param Collection<int, BlockTypeDefinition> $definitions */
    private function formatBlockTypeOptions(Collection $definitions): array
    {
        $currentLocale = app()->getLocale();

        return $definitions->map(static function (BlockTypeDefinition $bt) use ($currentLocale): array {
            $labels = (array) data_get($bt->schema, 'translations.labels', []);
            $descriptions = (array) data_get($bt->schema, 'translations.descriptions', []);
            $label = $labels[$currentLocale] ?? collect($labels)->first() ?? $bt->label;
            $description = $descriptions[$currentLocale] ?? collect($descriptions)->first() ?? $bt->description;

            return [
                'key' => (string) $bt->key,
                'label' => (string) $label,
                'description' => $description !== null && $description !== '' ? (string) $description : null,
                'icon' => (string) ($bt->icon ?: 'bi-box'),
            ];
        })->values()->all();
    }

    private function formatBlockTypeEditors(Collection $definitions, array $localeCodes): array
    {
        return $definitions->mapWithKeys(function (BlockTypeDefinition $bt) use ($localeCodes): array {
            $fields = collect((array) data_get($bt->schema, 'fields', []))
                ->map(static function (array $field) use ($localeCodes): array {
                    $fieldKey = trim((string) ($field['key'] ?? ''));
                    $labels = (array) ($field['labels'] ?? []);
                    $helps = (array) ($field['helps'] ?? []);

                    $localizedLabels = [];
                    $localizedHelps = [];
                    foreach ($localeCodes as $code) {
                        $localizedLabels[$code] = trim((string) ($labels[$code] ?? ''));
                        $localizedHelps[$code] = trim((string) ($helps[$code] ?? ''));
                    }

                    $normalizedField = [
                        'key' => $fieldKey,
                        'type' => trim((string) ($field['type'] ?? 'text')),
                        'label' => trim((string) ($field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey)))),
                        'labels' => $localizedLabels,
                        'help' => trim((string) ($field['help'] ?? '')),
                        'helps' => $localizedHelps,
                    ];

                    if (isset($field['options']) && is_array($field['options'])) {
                        $normalizedField['options'] = $field['options'];
                    }

                    if (($normalizedField['type'] ?? '') === 'repeater') {
                        $normalizedField['fields'] = (array) ($field['fields'] ?? []);
                        $normalizedField['add_button_label'] = (string) ($field['add_button_label'] ?? 'Add item');
                        $normalizedField['add_button_labels'] = (array) ($field['add_button_labels'] ?? []);
                    }

                    return $normalizedField;
                })
                ->filter(static fn (array $f): bool => (string) $f['key'] !== '')
                ->values()->all();

            return [
                (string) $bt->key => [
                    'key' => (string) $bt->key,
                    'label' => (string) $bt->label,
                    'description' => $bt->description !== null ? (string) $bt->description : null,
                    'icon' => (string) ($bt->icon ?: 'bi-box'),
                    'default_data' => (array) ($bt->default_data ?? []),
                    'fields' => $fields,
                ],
            ];
        })->all();
    }

    private function resolveLocaleBlocks(
        array $localeCodes,
        array $selectedBlockTypes,
        array $blockTypeEditors,
        ?Product $product
    ): array {
        $oldBlocks = old('blocks');
        $hasOldBlocks = is_array($oldBlocks) && $oldBlocks !== [];
        $productTranslations = $product?->translations->keyBy('locale');
        $resolved = [];

        foreach ($localeCodes as $localeCode) {
            $localeEntries = [];
            $translation = $productTranslations?->get($localeCode);
            $translationBlocksByType = $translation instanceof ProductTranslation
                ? $translation->blocks->sortBy('sort_order')->groupBy('type')
                : collect();
            $typeOffsets = [];
            $oldTypeOffsets = [];

            foreach ($selectedBlockTypes as $index => $blockKey) {
                $editor = $blockTypeEditors[$blockKey] ?? null;
                if (! is_array($editor)) {
                    continue;
                }

                $defaultData = (array) ($editor['default_data'] ?? []);
                $oldOccurrence = $oldTypeOffsets[$blockKey] ?? 0;
                $oldTypeOffsets[$blockKey] = $oldOccurrence + 1;
                $instanceKey = sprintf('%s__%d', $blockKey, $index);
                $payload = $hasOldBlocks
                    ? $this->resolveOldLocaleBlockPayload((array) ($oldBlocks[$localeCode] ?? []), $blockKey, $oldOccurrence)
                    : [];

                if ($translation instanceof ProductTranslation && ($payload === [] || ! $this->blockDataHasContent((array) ($payload['data'] ?? [])))) {
                    $existingBlock = $translationBlocksByType->get($blockKey)?->get($typeOffsets[$blockKey] ?? 0);
                    $typeOffsets[$blockKey] = ($typeOffsets[$blockKey] ?? 0) + 1;

                    if ($existingBlock !== null) {
                        $payload = ['sort_order' => (int) $existingBlock->sort_order, 'data' => (array) ($existingBlock->data ?? [])];
                    }
                }

                if ($productTranslations !== null && ($payload === [] || ! $this->blockDataHasContent((array) ($payload['data'] ?? [])))) {
                    $fallbackBlock = $productTranslations
                        ->flatMap(static fn (ProductTranslation $t) => $t->blocks)
                        ->first(function ($item) use ($blockKey): bool {
                            return (string) $item->type === $blockKey && $this->blockDataHasContent((array) ($item->data ?? []));
                        });

                    if ($fallbackBlock !== null) {
                        $payload = ['sort_order' => (int) ($payload['sort_order'] ?? $index), 'data' => (array) ($fallbackBlock->data ?? [])];
                    }
                }

                $sortOrder = (int) ($payload['sort_order'] ?? $index);
                $submittedData = (array) ($payload['data'] ?? []);
                $data = [];
                foreach ((array) ($editor['fields'] ?? []) as $field) {
                    $fieldKey = (string) ($field['key'] ?? '');
                    if ($fieldKey === '') {
                        continue;
                    }
                    $data[$fieldKey] = $submittedData[$fieldKey] ?? ($defaultData[$fieldKey] ?? '');
                }

                $localeEntries[] = ['instance_key' => $instanceKey, 'key' => $blockKey, 'sort_order' => $sortOrder, 'data' => $data];
            }

            $resolved[$localeCode] = collect($localeEntries)->sortBy('sort_order')->values()->all();
        }

        return $resolved;
    }

    private function resolveBlockTypesFromTranslations(Product $product): array
    {
        $primaryTranslation = $product->translations
            ->first(static fn (ProductTranslation $translation): bool => $translation->blocks->isNotEmpty());

        if ($primaryTranslation instanceof ProductTranslation) {
            return $primaryTranslation->blocks
                ->sortBy('sort_order')
                ->pluck('type')
                ->map(static fn ($type): string => trim((string) $type))
                ->filter(static fn (string $type): bool => $type !== '')
                ->values()
                ->all();
        }

        return $product->translations
            ->flatMap(static fn (ProductTranslation $t) => $t->blocks->sortBy('sort_order')->pluck('type'))
            ->map(static fn ($type): string => trim((string) $type))
            ->filter(static fn (string $type): bool => $type !== '')
            ->values()->all();
    }

    private function blockDataHasContent(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                if ($value !== []) {
                    return true;
                }

                continue;
            }
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function normalizeSelectedBlockTypes(array $selected, Collection $definitions): array
    {
        $allowed = $definitions->pluck('key')->map(static fn ($k): string => (string) $k);

        return collect($selected)
            ->map(static fn ($v): string => trim((string) $v))
            ->filter(static fn (string $v): bool => $v !== '' && $allowed->contains($v))
            ->values()->all();
    }

    private function normalizeBlockTypes(array $blockTypes): array
    {
        return collect($blockTypes)
            ->map(static fn ($v): string => trim((string) $v))
            ->filter(static fn (string $v): bool => $v !== '')
            ->values()->all();
    }

    /**
     * @param  array<string, mixed>  $localeBlocks
     * @return array<string, mixed>
     */
    private function resolveOldLocaleBlockPayload(array $localeBlocks, string $blockKey, int $occurrence): array
    {
        $matches = collect($localeBlocks)
            ->filter(static fn ($payload): bool => is_array($payload))
            ->map(static fn (array $payload): array => $payload)
            ->filter(static fn (array $payload) => trim((string) ($payload['type'] ?? $payload['key'] ?? '')) === $blockKey)
            ->sortBy(static fn (array $payload): int => (int) ($payload['sort_order'] ?? 0))
            ->values();

        return (array) ($matches->get($occurrence) ?? []);
    }

    private function defaultLocaleCode(array $locales): string
    {
        $default = collect($locales)->firstWhere('is_default', true);
        if (is_array($default) && isset($default['code'])) {
            return (string) $default['code'];
        }

        return (string) (collect($locales)->first()['code'] ?? app()->getLocale());
    }

    private function selectedLocaleCodesFromOldInput(array $locales, array $fallback): array
    {
        $allowed = collect($locales)->pluck('code')->map(static fn ($c): string => (string) $c)->values();
        $oldNames = collect(array_keys((array) old('names', [])))->map(static fn ($k): string => (string) $k);
        $oldSlugs = collect(array_keys((array) old('slugs', [])))->map(static fn ($k): string => (string) $k);
        $fromOld = $oldNames->merge($oldSlugs)->filter(static fn (string $c): bool => $allowed->contains($c))->unique()->values();

        if ($fromOld->isNotEmpty()) {
            return $fromOld->all();
        }

        return collect($fallback)->map(static fn ($c): string => (string) $c)->filter(static fn (string $c): bool => $allowed->contains($c))->unique()->values()->all();
    }

    private function availablePages(): array
    {
        $currentLocale = (string) app()->getLocale();

        return Page::query()->with('translations')->orderBy('sort_order')->orderBy('id')->get()
            ->map(static function (Page $page) use ($currentLocale): array {
                $localized = $page->translations->firstWhere('locale', $currentLocale);
                $fallback = $page->translations->first();

                return [
                    'id' => (int) $page->id,
                    'title' => (string) ($localized?->title ?? $fallback?->title ?? '#'.$page->id),
                    'slug' => (string) ($localized?->slug ?? $fallback?->slug ?? ''),
                    'published' => (bool) $page->published,
                ];
            })->values()->all();
    }

    private function selectedPageIdsFromOldInput(array $fallback): array
    {
        $fromOld = old('page_ids');
        if (is_array($fromOld)) {
            return $this->normalizeSelectedPageIds($fromOld);
        }

        return $this->normalizeSelectedPageIds($fallback);
    }

    private function normalizeSelectedPageIds(array $pageIds): array
    {
        return collect($pageIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()->values()->all();
    }

    private function resolveFallbackCategory(array $categories): ?string
    {
        return collect($categories)
            ->map(static fn ($value): string => trim((string) $value))
            ->first(static fn (string $value): bool => $value !== '');
    }
}
