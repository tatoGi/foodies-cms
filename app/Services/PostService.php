<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\BlockTypeDefinition;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\Website\RevalidateFrontendService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly BlockTypeRepositoryInterface $blockTypeRepository,
        private readonly LanguageRepositoryInterface $languageRepository,
        private readonly BlockNormalizationService $blockNormalizationService,
        private readonly RevalidateFrontendService $frontend,
    ) {}

    /**
     * @return array{posts: LengthAwarePaginator, currentLocale: string}
     */
    public function buildIndexViewData(): array
    {
        return [
            'posts' => $this->postRepository->paginateWithTranslations(15),
            'currentLocale' => app()->getLocale(),
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
        $selectedLocaleCodes = $this->selectedLocaleCodesFromOldInput($locales, $localeCodes);
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('post');
        $selectedBlockTypes = $this->normalizeSelectedBlockTypes(
            (array) old('block_types', []),
            $blockDefinitions
        );
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildEditViewData(Post $post): array
    {
        $post->load('translations.blocks');

        $locales = $this->languageRepository->getActiveLocales();
        $localeCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $fallbackSelected = $localeCodes;
        $selectedLocaleCodes = $this->selectedLocaleCodesFromOldInput($locales, $fallbackSelected);
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('post');
        $storedBlockTypes = $this->normalizeBlockTypes((array) ($post->block_types ?? []));
        if ($storedBlockTypes === []) {
            $storedBlockTypes = $this->resolveBlockTypesFromTranslations($post);
        }
        $selectedBlockTypes = $this->normalizeSelectedBlockTypes(
            (array) old('block_types', $storedBlockTypes),
            $blockDefinitions
        );
        $blockTypeOptions = $this->formatBlockTypeOptions($blockDefinitions);
        $blockTypeEditors = $this->formatBlockTypeEditors($blockDefinitions, $localeCodes);
        $localeBlocks = $this->resolveLocaleBlocks($localeCodes, $selectedBlockTypes, $blockTypeEditors, $post);
        $selectedPageIds = $this->selectedPageIdsFromOldInput(
            $post->pages()->pluck('pages.id')->map(static fn ($id): int => (int) $id)->all()
        );

        return [
            'post' => $post,
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
            'translations' => $post->translations->keyBy('locale'),
            'blockTypes' => $blockTypeOptions,
            'blockTypeEditors' => $blockTypeEditors,
            'selectedBlockTypes' => $selectedBlockTypes,
            'localeBlocks' => $localeBlocks,
            'availablePages' => $this->availablePages(),
            'selectedPageIds' => $selectedPageIds,
        ];
    }

    public function create(StorePostRequest $request): Post
    {
        $post = DB::transaction(function () use ($request): Post {
            $post = $this->postRepository->create([
                'category' => trim((string) $request->input('category', '')) ?: null,
                'feature_image' => trim((string) $request->input('feature_image', '')) ?: null,
                'published_at' => $request->input('published_at') ?: null,
                'block_types' => $this->normalizeBlockTypes((array) $request->input('block_types', [])),
                'published' => $request->boolean('published'),
            ]);

            $this->syncTranslations(
                $post,
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
            $post->pages()->sync(
                $this->normalizeSelectedPageIds((array) $request->input('page_ids', []))
            );

            return $post;
        });
        $this->frontend->revalidate($this->frontendTags($post));

        return $post;
    }

    public function update(UpdatePostRequest $request, Post $post): Post
    {
        $post = DB::transaction(function () use ($request, $post): Post {
            $this->postRepository->update($post, [
                'category' => trim((string) $request->input('category', '')) ?: null,
                'feature_image' => trim((string) $request->input('feature_image', '')) ?: null,
                'published_at' => $request->input('published_at') ?: null,
                'block_types' => $this->normalizeBlockTypes((array) $request->input('block_types', [])),
                'published' => $request->boolean('published'),
            ]);

            $this->syncTranslations(
                $post,
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
            $post->pages()->sync(
                $this->normalizeSelectedPageIds((array) $request->input('page_ids', []))
            );

            return $post;
        });
        $this->frontend->revalidate($this->frontendTags($post));

        return $post;
    }

    public function delete(Post $post): void
    {
        $tags = $this->frontendTags($post);
        $this->postRepository->delete($post);
        $this->frontend->revalidate($tags);
    }

    /** The blog list, each post page and the pages that show latest news. */
    private function frontendTags(Post $post): array
    {
        $slugs = $post->translations()->pluck('slug')
            ->filter(fn (mixed $slug): bool => is_string($slug) && $slug !== '')
            ->map(fn (string $slug): string => 'post:'.$slug)
            ->all();

        return array_values(array_merge(['posts', 'pages'], $slugs));
    }

    public function reorder(array $orderedIds): void
    {
        $this->postRepository->reorderByIds($orderedIds);
    }

    /**
     * @param  array<string, mixed>  $names
     * @param  array<string, mixed>  $slugs
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
        Post $post,
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
        $selectedBlockTypes = $this->normalizeBlockTypes((array) ($post->block_types ?? []));
        $blockDefinitions = $this->blockTypeRepository->getEnabledForScope('post')
            ->keyBy(static fn (BlockTypeDefinition $blockType): string => (string) $blockType->key);

        $handledLocales = [];
        foreach ($localeCodes as $locale) {
            $name = trim((string) ($names[$locale] ?? ''));
            $slug = trim((string) ($slugs[$locale] ?? ''));
            $content = trim((string) ($descriptions[$locale] ?? ''));
            $metaTitle = trim((string) ($metaTitles[$locale] ?? ''));
            $metaDescription = trim((string) ($metaDescriptions[$locale] ?? ''));
            $keyword = trim((string) ($keywords[$locale] ?? ''));
            $focusKeyword = trim((string) ($focusKeywords[$locale] ?? ''));
            $canonicalUrl = trim((string) ($canonicalUrls[$locale] ?? ''));

            if ($name !== '' || $slug !== '') {
                $translation = $post->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $name,
                        'slug' => $slug,
                        'content' => $content !== '' ? $content : null,
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

        $post->translations()
            ->whereNotIn('locale', $handledLocales)
            ->delete();
    }

    /**
     * @param  array<int, string>  $selectedBlockTypes
     * @param  Collection<string, BlockTypeDefinition>  $blockDefinitions
     */
    private function syncTranslationBlocks(
        PostTranslation $translation,
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
        ?Post $post
    ): array {
        $oldBlocks = old('blocks');
        $hasOldBlocks = is_array($oldBlocks) && $oldBlocks !== [];
        $postTranslations = $post?->translations->keyBy('locale');
        $resolved = [];

        foreach ($localeCodes as $localeCode) {
            $localeEntries = [];
            $translation = $postTranslations?->get($localeCode);
            $translationBlocksByType = $translation instanceof PostTranslation
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

                if ($translation instanceof PostTranslation && ($payload === [] || ! $this->blockDataHasContent((array) ($payload['data'] ?? [])))) {
                    $existingBlock = $translationBlocksByType->get($blockKey)?->get($typeOffsets[$blockKey] ?? 0);
                    $typeOffsets[$blockKey] = ($typeOffsets[$blockKey] ?? 0) + 1;

                    if ($existingBlock !== null) {
                        $payload = [
                            'sort_order' => (int) $existingBlock->sort_order,
                            'data' => (array) ($existingBlock->data ?? []),
                        ];
                    }
                }

                if ($postTranslations !== null && ($payload === [] || ! $this->blockDataHasContent((array) ($payload['data'] ?? [])))) {
                    $fallbackBlock = $postTranslations
                        ->flatMap(static fn (PostTranslation $item) => $item->blocks)
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
    private function resolveBlockTypesFromTranslations(Post $post): array
    {
        $primaryTranslation = $post->translations
            ->first(static fn (PostTranslation $translation): bool => $translation->blocks->isNotEmpty());

        if ($primaryTranslation instanceof PostTranslation) {
            return $primaryTranslation->blocks
                ->sortBy('sort_order')
                ->pluck('type')
                ->map(static fn ($type): string => trim((string) $type))
                ->filter(static fn (string $type): bool => $type !== '')
                ->values()
                ->all();
        }

        return $post->translations
            ->flatMap(static fn (PostTranslation $translation) => $translation->blocks->sortBy('sort_order')->pluck('type'))
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
            ->sortBy(static fn (array $payload): int => (int) ($payload['sort_order'] ?? 0))
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
     * @return array<int, array{id:int,title:string,slug:string,published:bool}>
     */
    private function availablePages(): array
    {
        $currentLocale = (string) app()->getLocale();

        return Page::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static function (Page $page) use ($currentLocale): array {
                $localized = $page->translations->firstWhere('locale', $currentLocale);
                $fallback = $page->translations->first();

                return [
                    'id' => (int) $page->id,
                    'title' => (string) ($localized?->title ?? $fallback?->title ?? '#'.$page->id),
                    'slug' => (string) ($localized?->slug ?? $fallback?->slug ?? ''),
                    'published' => (bool) $page->published,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $fallback
     * @return array<int, int>
     */
    private function selectedPageIdsFromOldInput(array $fallback): array
    {
        $fromOld = old('page_ids');
        if (is_array($fromOld)) {
            return $this->normalizeSelectedPageIds($fromOld);
        }

        return $this->normalizeSelectedPageIds($fallback);
    }

    /**
     * @param  array<int, mixed>  $pageIds
     * @return array<int, int>
     */
    private function normalizeSelectedPageIds(array $pageIds): array
    {
        return collect($pageIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
