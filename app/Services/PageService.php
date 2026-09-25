<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\BlockTypeDefinition;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSlugAlias;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\Product;
use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Services\Website\RevalidateFrontendService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PageService
{
    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
        private readonly BlockTypeRepositoryInterface $blockTypeRepository,
        private readonly LanguageRepositoryInterface $languageRepository,
        private readonly BlockNormalizationService $blockNormalizationService,
        private readonly MenuPageSlugSyncService $menuPageSlugSyncService,
        private readonly RevalidateFrontendService $frontend,
    ) {}

    /**
     * @return array{pages: LengthAwarePaginator, currentLocale: string, pageTree: array<int, array{id:int,page:Page,children:array<int, mixed>}>}
     */
    public function buildIndexViewData(): array
    {
        $treeSource = $this->pageRepository->allWithTranslationsAndTemplateOrdered();

        return [
            'pages' => $this->pageRepository->paginateWithTranslationsAndTemplate(10),
            'currentLocale' => app()->getLocale(),
            'pageTree' => $this->buildPageTree($treeSource),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildCreateViewData(): array
    {
        $locales = $this->languageRepository->getActiveLocales();
        $localeCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $allLocaleCodes = $localeCodes;
        $selectedLocaleCodes = $this->selectedLocaleCodesFromOldInput($locales, $allLocaleCodes);
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('page');
        $selectedBlockTypes = $this->normalizeSelectedBlockTypes(
            (array) old('block_types', []),
            $blockDefinitions
        );
        $blockTypeOptions = $this->formatBlockTypeOptions($blockDefinitions);
        $blockTypeEditors = $this->formatBlockTypeEditors($blockDefinitions, $localeCodes);
        $localeBlocks = $this->resolveLocaleBlocks($localeCodes, $selectedBlockTypes, $blockTypeEditors, null);
        $selectedPostIds = $this->selectedPostIdsFromOldInput([]);
        $selectedProductIds = $this->selectedProductIdsFromOldInput([]);

        return [
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
            'templates' => $this->pageRepository->allTemplates(),
            'parentPages' => $this->pageRepository->allExcept(),
            'blockTypes' => $blockTypeOptions,
            'blockTypeEditors' => $blockTypeEditors,
            'selectedBlockTypes' => $selectedBlockTypes,
            'localeBlocks' => $localeBlocks,
            'availablePosts' => $this->availablePosts(),
            'selectedPostIds' => $selectedPostIds,
            'availableProducts' => $this->availableProducts(),
            'selectedProductIds' => $selectedProductIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildEditViewData(Page $page): array
    {
        $page->load('translations.blocks');

        $locales = $this->languageRepository->getActiveLocales();
        $localeCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $fallbackSelected = $localeCodes;
        $selectedLocaleCodes = $this->selectedLocaleCodesFromOldInput($locales, $fallbackSelected);
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('page');
        $storedBlockTypes = $this->normalizeBlockTypes((array) ($page->block_types ?? []));
        if ($storedBlockTypes === []) {
            $storedBlockTypes = $this->resolveBlockTypesFromTranslations($page);
        }
        $selectedBlockTypes = $this->normalizeSelectedBlockTypes(
            (array) old('block_types', $storedBlockTypes),
            $blockDefinitions
        );
        $blockTypeOptions = $this->formatBlockTypeOptions($blockDefinitions);
        $blockTypeEditors = $this->formatBlockTypeEditors($blockDefinitions, $localeCodes);
        $localeBlocks = $this->resolveLocaleBlocks($localeCodes, $selectedBlockTypes, $blockTypeEditors, $page);
        $selectedPostIds = $this->selectedPostIdsFromOldInput(
            $page->posts()->pluck('posts.id')->map(static fn ($id): int => (int) $id)->all()
        );
        $selectedProductIds = $this->selectedProductIdsFromOldInput(
            $page->products()->pluck('products.id')->map(static fn ($id): int => (int) $id)->all()
        );

        return [
            'page' => $page,
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
            'templates' => $this->pageRepository->allTemplates(),
            'parentPages' => $this->pageRepository->allExcept((int) $page->id),
            'translations' => $page->translations->keyBy('locale'),
            'blockTypes' => $blockTypeOptions,
            'blockTypeEditors' => $blockTypeEditors,
            'selectedBlockTypes' => $selectedBlockTypes,
            'localeBlocks' => $localeBlocks,
            'availablePosts' => $this->availablePosts(),
            'selectedPostIds' => $selectedPostIds,
            'availableProducts' => $this->availableProducts(),
            'selectedProductIds' => $selectedProductIds,
        ];
    }

    public function create(StorePageRequest $request): Page
    {
        $page = DB::transaction(function () use ($request): Page {
            $page = $this->pageRepository->create([
                'parent_id' => $request->boolean('is_home') ? null : ($request->integer('parent_id') ?: null),
                'template' => (string) $request->string('template'),
                'block_types' => $this->normalizeBlockTypes((array) $request->input('block_types', [])),
                'is_home' => $request->boolean('is_home'),
                'sort_order' => $request->integer('sort_order'),
                'published' => $request->boolean('published'),
                'feature_image' => trim((string) $request->input('feature_image', '')) ?: null,
            ]);

            if ($page->is_home) {
                $this->pageRepository->clearHomeFlagExcept((int) $page->id);
            }

            $this->syncTranslations(
                $page,
                (array) $request->input('names', []),
                (array) $request->input('slugs', []),
                (array) $request->input('descriptions', []),
                (array) $request->input('meta_titles', []),
                (array) $request->input('meta_descriptions', []),
                (array) $request->input('keywords', []),
                (array) $request->input('focus_keywords', []),
                (array) $request->input('canonical_urls', []),
                (array) $request->input('blocks', []),
                (array) $request->file('blocks', [])
            );
            $page->posts()->sync(
                $this->normalizeSelectedPostIds((array) $request->input('post_ids', []))
            );
            $page->products()->sync(
                $this->normalizeSelectedProductIds((array) $request->input('product_ids', []))
            );
            $this->menuPageSlugSyncService->syncMenuItemsFromPage((int) $page->id);

            return $page;
        });

        $this->refreshFrontend($page);

        return $page;
    }

    public function update(UpdatePageRequest $request, Page $page): Page
    {
        $page = DB::transaction(function () use ($request, $page): Page {
            $this->pageRepository->update($page, [
                'parent_id' => $request->boolean('is_home') ? null : ($request->integer('parent_id') ?: null),
                'template' => (string) $request->string('template'),
                'block_types' => $this->normalizeBlockTypes((array) $request->input('block_types', [])),
                'is_home' => $request->boolean('is_home'),
                'sort_order' => $request->integer('sort_order'),
                'published' => $request->boolean('published'),
                'feature_image' => trim((string) $request->input('feature_image', '')) ?: null,
            ]);

            if ($request->boolean('is_home')) {
                $this->pageRepository->clearHomeFlagExcept((int) $page->id);
            }

            $this->syncTranslations(
                $page,
                (array) $request->input('names', []),
                (array) $request->input('slugs', []),
                (array) $request->input('descriptions', []),
                (array) $request->input('meta_titles', []),
                (array) $request->input('meta_descriptions', []),
                (array) $request->input('keywords', []),
                (array) $request->input('focus_keywords', []),
                (array) $request->input('canonical_urls', []),
                (array) $request->input('blocks', []),
                (array) $request->file('blocks', [])
            );
            $page->posts()->sync(
                $this->normalizeSelectedPostIds((array) $request->input('post_ids', []))
            );
            $page->products()->sync(
                $this->normalizeSelectedProductIds((array) $request->input('product_ids', []))
            );
            $this->menuPageSlugSyncService->syncMenuItemsFromPage((int) $page->id);

            return $page;
        });

        $this->refreshFrontend($page);

        return $page;
    }

    public function delete(Page $page): void
    {
        $page->loadMissing('translations');
        $tags = $this->frontendTags($page);
        $this->pageRepository->delete($page);
        $this->frontend->revalidate($tags);
    }

    private function refreshFrontend(Page $page): void
    {
        $page->load('translations');
        $this->frontend->revalidate($this->frontendTags($page));
    }

    /**
     * @return list<string>
     */
    private function frontendTags(Page $page): array
    {
        $tags = ['pages'];
        if ($page->is_home) {
            $tags[] = 'page:home';
        }

        foreach ($page->translations as $translation) {
            $slug = trim((string) $translation->slug);
            if ($slug !== '') {
                $tags[] = 'page:'.$slug;
            }
        }

        return $tags;
    }

    public function reorder(array $orderedIds): void
    {
        $this->pageRepository->reorderByIds($orderedIds);

        $updates = array_map(
            fn (int $position, mixed $id): array => ['id' => (int) $id, 'parent_id' => null, 'sort_order' => $position],
            array_keys($orderedIds),
            $orderedIds
        );
        $this->syncMenuItemsOrder($updates);
    }

    /**
     * @param  array<int, array{id:mixed,children?:array<int, mixed>}>  $tree
     */
    public function reorderTree(array $tree): void
    {
        $allIds = $this->pageRepository->allIds();
        if ($allIds === []) {
            return;
        }

        $allowedIds = array_flip($allIds);
        $seen = [];
        $updates = [];
        $position = 0;

        $walk = function (array $nodes, ?int $parentId) use (&$walk, &$seen, &$updates, &$position, $allowedIds): void {
            foreach ($nodes as $node) {
                if (! is_array($node)) {
                    continue;
                }

                $id = (int) ($node['id'] ?? 0);
                if ($id < 1 || ! isset($allowedIds[$id]) || isset($seen[$id])) {
                    continue;
                }

                if ($parentId !== null && $id === $parentId) {
                    continue;
                }

                $seen[$id] = true;
                $updates[] = [
                    'id' => $id,
                    'parent_id' => $parentId,
                    'sort_order' => $position,
                ];
                $position++;

                $children = $node['children'] ?? [];
                if (is_array($children) && $children !== []) {
                    $walk($children, $id);
                }
            }
        };

        $walk($tree, null);

        foreach ($allIds as $id) {
            if (isset($seen[$id])) {
                continue;
            }

            $updates[] = [
                'id' => $id,
                'parent_id' => null,
                'sort_order' => $position,
            ];
            $position++;
        }

        $this->pageRepository->updateHierarchy($updates);
        $this->syncMenuItemsOrder($updates);
    }

    /**
     * After reordering pages, sync menu_items.order for page-type items
     * so the navigation menu reflects the new page sort_order.
     *
     * @param  array<int, array{id:int,parent_id:?int,sort_order:int}>  $updates
     */
    private function syncMenuItemsOrder(array $updates): void
    {
        if ($updates === []) {
            return;
        }

        foreach ($updates as $update) {
            MenuItem::query()
                ->where('type', 'page')
                ->where('reference_id', $update['id'])
                ->update(['order' => $update['sort_order']]);
        }
    }

    /**
     * @param  array<string, mixed>  $names
     * @param  array<string, mixed>  $descriptions
     * @param  array<string, mixed>  $metaTitles
     * @param  array<string, mixed>  $metaDescriptions
     * @param  array<string, mixed>  $keywords
     * @param  array<string, mixed>  $focusKeywords
     * @param  array<string, mixed>  $canonicalUrls
     * @param  array<string, mixed>  $blocksByLocale
     * @param  array<string, mixed>  $blockFilesByLocale
     */
    private function syncTranslations(
        Page $page,
        array $names,
        array $slugs,
        array $descriptions,
        array $metaTitles,
        array $metaDescriptions,
        array $keywords,
        array $focusKeywords,
        array $canonicalUrls,
        array $blocksByLocale,
        array $blockFilesByLocale
    ): void {
        $localeCodes = collect($this->languageRepository->getActiveLocales())
            ->pluck('code')
            ->map(static fn ($locale): string => (string) $locale)
            ->values();
        $selectedBlockTypes = $this->normalizeBlockTypes((array) ($page->block_types ?? []));
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('page')
            ->keyBy(static fn (BlockTypeDefinition $blockType): string => (string) $blockType->key);

        $handledLocales = [];
        foreach ($localeCodes as $locale) {
            $name = trim((string) ($names[$locale] ?? ''));
            $description = trim((string) ($descriptions[$locale] ?? ''));
            $metaTitle = trim((string) ($metaTitles[$locale] ?? ''));
            $metaDescription = trim((string) ($metaDescriptions[$locale] ?? ''));
            $keyword = trim((string) ($keywords[$locale] ?? ''));
            $focusKeyword = trim((string) ($focusKeywords[$locale] ?? ''));
            $canonicalUrl = trim((string) ($canonicalUrls[$locale] ?? ''));

            $slug = trim((string) ($slugs[$locale] ?? ''));
            $previousSlug = trim((string) ($page->translations()->where('locale', $locale)->value('slug') ?? ''));

            if ($name !== '') {
                $this->rememberPreviousSlug($page, $locale, $previousSlug, $slug);
                $translation = $page->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $name,
                        'slug' => $slug,
                        'description' => $description !== '' ? $description : null,
                        'meta_title' => $metaTitle !== '' ? $metaTitle : null,
                        'meta_description' => $metaDescription !== '' ? $metaDescription : null,
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

        $page->translations()
            ->whereNotIn('locale', $handledLocales)
            ->delete();
    }

    public function rememberPreviousSlug(Page $page, string $locale, string $previousSlug, string $nextSlug): void
    {
        $previousSlug = trim($previousSlug);
        $nextSlug = trim($nextSlug);

        if ($nextSlug !== '') {
            PageSlugAlias::query()->where('page_id', $page->id)->where('slug', $nextSlug)->delete();
        }

        if ($previousSlug === '' || $previousSlug === $nextSlug) {
            return;
        }

        $takenByAnotherPage = PageTranslation::query()
            ->where('slug', $previousSlug)
            ->where('page_id', '!=', $page->id)
            ->exists();

        if ($takenByAnotherPage) {
            return;
        }

        PageSlugAlias::query()->updateOrCreate(
            ['slug' => $previousSlug],
            ['page_id' => $page->id, 'locale' => $locale]
        );
    }

    /**
     * @param  array<int, string>  $selectedBlockTypes
     * @param  Collection<string, BlockTypeDefinition>  $blockDefinitions
     */
    private function syncTranslationBlocks(
        PageTranslation $translation,
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

                /** @var BlockTypeDefinition|null $definition */
                $definition = $blockDefinitions->get($blockKey);
                if (! $definition instanceof BlockTypeDefinition) {
                    return null;
                }

                $availableTypeCounts[$blockKey]--;
                $instanceKey = trim((string) ($payload['instance_key'] ?? $instanceKey));
                $instanceFiles = [];
                if ($instanceKey !== '') {
                    $instanceFiles = (array) ($localeBlockFiles[$instanceKey] ?? []);
                }

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
            ->values();

        $translation->blocks()->delete();
        foreach ($normalizedBlocks as $position => $block) {
            $translation->blocks()->create([
                'type' => (string) $block['type'],
                'data' => (array) $block['data'],
                'sort_order' => $position,
            ]);
        }
    }

    /**
     * @param  Collection<int, BlockTypeDefinition>  $definitions
     * @return array<int, array{key:string,label:string,description:?string,icon:string}>
     */
    private function formatBlockTypeOptions(Collection $definitions): array
    {
        $currentLocale = app()->getLocale();

        return $definitions
            ->map(static function (BlockTypeDefinition $blockType) use ($currentLocale): array {
                $labels = (array) data_get($blockType->schema, 'translations.labels', []);
                $descriptions = (array) data_get($blockType->schema, 'translations.descriptions', []);
                $label = $labels[$currentLocale] ?? collect($labels)->first() ?? $blockType->label;
                $description = $descriptions[$currentLocale] ?? collect($descriptions)->first() ?? $blockType->description;

                return [
                    'key' => (string) $blockType->key,
                    'label' => (string) $label,
                    'description' => $description !== null && $description !== '' ? (string) $description : null,
                    'icon' => (string) ($blockType->icon ?: 'bi-box'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, BlockTypeDefinition>  $definitions
     * @param  array<int, string>  $localeCodes
     * @return array<string, array{key:string,label:string,description:?string,icon:string,default_data:array<string,mixed>,fields:array<int,array<string,mixed>>}>
     */
    private function formatBlockTypeEditors(Collection $definitions, array $localeCodes): array
    {
        return $definitions
            ->mapWithKeys(function (BlockTypeDefinition $blockType) use ($localeCodes): array {
                $fields = collect((array) data_get($blockType->schema, 'fields', []))
                    ->map(static function (array $field) use ($localeCodes): array {
                        $fieldKey = trim((string) ($field['key'] ?? ''));
                        $fieldType = trim((string) ($field['type'] ?? 'text'));
                        $labels = (array) ($field['labels'] ?? []);
                        $helps = (array) ($field['helps'] ?? []);

                        $localizedLabels = [];
                        $localizedHelps = [];
                        foreach ($localeCodes as $localeCode) {
                            $localizedLabels[$localeCode] = trim((string) ($labels[$localeCode] ?? ''));
                            $localizedHelps[$localeCode] = trim((string) ($helps[$localeCode] ?? ''));
                        }

                        $normalizedField = [
                            'key' => $fieldKey,
                            'type' => $fieldType,
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
                    ->filter(static fn (array $field): bool => (string) $field['key'] !== '')
                    ->values()
                    ->all();

                return [
                    (string) $blockType->key => [
                        'key' => (string) $blockType->key,
                        'label' => (string) $blockType->label,
                        'description' => $blockType->description !== null ? (string) $blockType->description : null,
                        'icon' => (string) ($blockType->icon ?: 'bi-box'),
                        'default_data' => (array) ($blockType->default_data ?? []),
                        'fields' => $fields,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, string>  $localeCodes
     * @param  array<int, string>  $selectedBlockTypes
     * @param  array<string, array{key:string,label:string,description:?string,icon:string,default_data:array<string,mixed>,fields:array<int,array<string,mixed>>}>  $blockTypeEditors
     * @return array<string, array<int, array{instance_key:string,key:string,sort_order:int,data:array<string,mixed>}>>
     */
    private function resolveLocaleBlocks(
        array $localeCodes,
        array $selectedBlockTypes,
        array $blockTypeEditors,
        ?Page $page
    ): array {
        $oldBlocks = old('blocks');
        $hasOldBlocks = is_array($oldBlocks) && $oldBlocks !== [];
        $pageTranslations = $page?->translations->keyBy('locale');
        $resolved = [];

        foreach ($localeCodes as $localeCode) {
            $localeEntries = [];
            $translation = $pageTranslations?->get($localeCode);
            $translationBlocksByType = $translation instanceof PageTranslation
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

                if ($translation instanceof PageTranslation && ($payload === [] || ! $this->blockDataHasContent((array) ($payload['data'] ?? [])))) {
                    $existingBlock = $translationBlocksByType->get($blockKey)?->get($typeOffsets[$blockKey] ?? 0);
                    $typeOffsets[$blockKey] = ($typeOffsets[$blockKey] ?? 0) + 1;

                    if ($existingBlock !== null) {
                        $payload = [
                            'sort_order' => (int) $existingBlock->sort_order,
                            'data' => (array) ($existingBlock->data ?? []),
                        ];
                    }
                }

                if ($pageTranslations !== null && ($payload === [] || ! $this->blockDataHasContent((array) ($payload['data'] ?? [])))) {
                    $fallbackBlock = $pageTranslations
                        ->flatMap(static fn (PageTranslation $item) => $item->blocks)
                        ->first(function ($item) use ($blockKey): bool {
                            if ((string) $item->type !== $blockKey) {
                                return false;
                            }

                            return $this->blockDataHasContent((array) ($item->data ?? []));
                        });

                    if ($fallbackBlock !== null) {
                        $payload = [
                            'sort_order' => (int) ($payload['sort_order'] ?? $index),
                            'data' => (array) ($fallbackBlock->data ?? []),
                        ];
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

                $localeEntries[] = [
                    'instance_key' => $instanceKey,
                    'key' => $blockKey,
                    'sort_order' => $sortOrder,
                    'data' => $data,
                ];
            }

            $resolved[$localeCode] = collect($localeEntries)
                ->sortBy('sort_order')
                ->values()
                ->all();
        }

        return $resolved;
    }

    /**
     * @return array<int, string>
     */
    private function resolveBlockTypesFromTranslations(Page $page): array
    {
        $primaryTranslation = $page->translations
            ->first(static fn (PageTranslation $translation): bool => $translation->blocks->isNotEmpty());

        if ($primaryTranslation instanceof PageTranslation) {
            return $primaryTranslation->blocks
                ->sortBy('sort_order')
                ->pluck('type')
                ->map(static fn ($type): string => trim((string) $type))
                ->filter(static fn (string $type): bool => $type !== '')
                ->values()
                ->all();
        }

        return $page->translations
            ->flatMap(static fn (PageTranslation $translation) => $translation->blocks->sortBy('sort_order')->pluck('type'))
            ->map(static fn ($type): string => trim((string) $type))
            ->filter(static fn (string $type): bool => $type !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @param  array<int, mixed>  $selected
     * @param  Collection<int, BlockTypeDefinition>  $definitions
     * @return array<int, string>
     */
    private function normalizeSelectedBlockTypes(array $selected, Collection $definitions): array
    {
        $allowed = $definitions
            ->pluck('key')
            ->map(static fn ($key): string => (string) $key);

        return collect($selected)
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '' && $allowed->contains($value))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $blockTypes
     * @return array<int, string>
     */
    private function normalizeBlockTypes(array $blockTypes): array
    {
        return collect($blockTypes)
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();
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
            ->sortBy(static fn (array $payload, $instanceKey): int => (int) ($payload['sort_order'] ?? 0))
            ->values();

        return (array) ($matches->get($occurrence) ?? []);
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
     * @param  array<int, array{code:string,name:string,flag:string,is_default:bool}>  $locales
     * @param  array<int, string>  $fallback
     * @return array<int, string>
     */
    private function selectedLocaleCodesFromOldInput(array $locales, array $fallback): array
    {
        $allowed = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values();

        $oldNames = collect(array_keys((array) old('names', [])))->map(static fn ($key): string => (string) $key);
        $oldSlugs = collect(array_keys((array) old('slugs', [])))->map(static fn ($key): string => (string) $key);
        $fromOld = $oldNames
            ->merge($oldSlugs)
            ->filter(static fn (string $code): bool => $allowed->contains($code))
            ->unique()
            ->values();

        if ($fromOld->isNotEmpty()) {
            return $fromOld->all();
        }

        return collect($fallback)
            ->map(static fn ($code): string => (string) $code)
            ->filter(static fn (string $code): bool => $allowed->contains($code))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return array<int, array{id:int,page:Page,children:array<int, mixed>}>
     */
    private function buildPageTree(Collection $pages): array
    {
        $pagesById = $pages->keyBy('id');
        $childrenByParent = [];

        foreach ($pages as $page) {
            $parentId = (int) ($page->parent_id ?? 0);
            if ($parentId === (int) $page->id || ($parentId > 0 && ! $pagesById->has($parentId))) {
                $parentId = 0;
            }

            if (! isset($childrenByParent[$parentId])) {
                $childrenByParent[$parentId] = [];
            }

            $childrenByParent[$parentId][] = (int) $page->id;
        }

        $rendered = [];

        $buildNode = function (int $pageId, array $ancestors = []) use (&$buildNode, $pagesById, $childrenByParent, &$rendered): ?array {
            /** @var Page|null $page */
            $page = $pagesById->get($pageId);
            if (! $page instanceof Page) {
                return null;
            }

            $rendered[$pageId] = true;
            $ancestorChain = [...$ancestors, $pageId];
            $children = [];

            foreach ($childrenByParent[$pageId] ?? [] as $childId) {
                if (in_array($childId, $ancestorChain, true)) {
                    continue;
                }

                $node = $buildNode($childId, $ancestorChain);
                if (is_array($node)) {
                    $children[] = $node;
                }
            }

            return [
                'id' => $pageId,
                'page' => $page,
                'children' => $children,
            ];
        };

        $tree = [];
        foreach ($childrenByParent[0] ?? [] as $rootId) {
            $node = $buildNode((int) $rootId);
            if (is_array($node)) {
                $tree[] = $node;
            }
        }

        foreach ($pagesById->keys()->map(static fn ($id): int => (int) $id)->all() as $pageId) {
            if (isset($rendered[$pageId])) {
                continue;
            }

            $node = $buildNode($pageId);
            if (is_array($node)) {
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * @return array<int, array{id:int,title:string,slug:string,published:bool}>
     */
    private function availablePosts(): array
    {
        $currentLocale = (string) app()->getLocale();

        return Post::query()
            ->with('translations')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(static function (Post $post) use ($currentLocale): array {
                $localized = $post->translations->firstWhere('locale', $currentLocale);
                $fallback = $post->translations->first();

                return [
                    'id' => (int) $post->id,
                    'title' => (string) ($localized?->title ?? $fallback?->title ?? '#'.$post->id),
                    'slug' => (string) ($localized?->slug ?? $fallback?->slug ?? ''),
                    'published' => (bool) $post->published,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $fallback
     * @return array<int, int>
     */
    private function selectedPostIdsFromOldInput(array $fallback): array
    {
        $fromOld = old('post_ids');
        if (is_array($fromOld)) {
            return $this->normalizeSelectedPostIds($fromOld);
        }

        return $this->normalizeSelectedPostIds($fallback);
    }

    /**
     * @param  array<int, mixed>  $postIds
     * @return array<int, int>
     */
    private function normalizeSelectedPostIds(array $postIds): array
    {
        return collect($postIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,title:string,sku:string,published:bool}>
     */
    private function availableProducts(): array
    {
        $currentLocale = (string) app()->getLocale();

        return Product::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static function (Product $product) use ($currentLocale): array {
                $localized = $product->translations->firstWhere('locale', $currentLocale);
                $fallback = $product->translations->first();

                return [
                    'id' => (int) $product->id,
                    'title' => (string) ($localized?->title ?? $fallback?->title ?? '#'.$product->id),
                    'slug' => (string) ($localized?->slug ?? $fallback?->slug ?? ''),
                    'sku' => (string) $product->sku,
                    'published' => (bool) $product->published,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $fallback
     * @return array<int, int>
     */
    private function selectedProductIdsFromOldInput(array $fallback): array
    {
        $fromOld = old('product_ids');
        if (is_array($fromOld)) {
            return $this->normalizeSelectedProductIds($fromOld);
        }

        return $this->normalizeSelectedProductIds($fallback);
    }

    /**
     * @param  array<int, mixed>  $productIds
     * @return array<int, int>
     */
    private function normalizeSelectedProductIds(array $productIds): array
    {
        return collect($productIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
