<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Reel;
use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

class AdminContentAiService
{
    public function __construct(
        private readonly AiContentService $aiContentService,
        private readonly LanguageRepositoryInterface $languageRepository,
        private readonly BlockTypeRepositoryInterface $blockTypeRepository,
    ) {}

    public function translatePage(Page $page, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->translateModel($page->load('translations.blocks'), 'page', 'description', $targetLocale, $sourceLocale);
    }

    public function generatePageSeo(Page $page, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->generateSeoForModel($page->load('translations.blocks'), 'page', 'description', $targetLocale, $sourceLocale);
    }

    public function translatePageDraft(array $translations, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->translateDraft($translations, 'page', $targetLocale, $sourceLocale);
    }

    public function generatePageDraftSeo(array $translations, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->generateSeoForDraft($translations, 'page', $targetLocale, $sourceLocale);
    }

    public function translatePost(Post $post, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->translateModel($post->load('translations.blocks'), 'post', 'content', $targetLocale, $sourceLocale);
    }

    public function generatePostSeo(Post $post, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->generateSeoForModel($post->load('translations.blocks'), 'post', 'content', $targetLocale, $sourceLocale);
    }

    public function translatePostDraft(array $translations, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->translateDraft($translations, 'post', $targetLocale, $sourceLocale);
    }

    public function generatePostDraftSeo(array $translations, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->generateSeoForDraft($translations, 'post', $targetLocale, $sourceLocale);
    }

    public function translateProduct(Product $product, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->translateModel($product->load('translations.blocks'), 'product', 'content', $targetLocale, $sourceLocale, ['category']);
    }

    public function generateProductSeo(Product $product, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->generateSeoForModel($product->load('translations.blocks'), 'product', 'content', $targetLocale, $sourceLocale);
    }

    public function translateProductDraft(array $translations, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->translateDraft($translations, 'product', $targetLocale, $sourceLocale, ['category']);
    }

    public function generateProductDraftSeo(array $translations, string $targetLocale, ?string $sourceLocale = null): array
    {
        return $this->generateSeoForDraft($translations, 'product', $targetLocale, $sourceLocale);
    }

    public function translateReel(Reel $reel, string $targetLocale, ?string $sourceLocale = null): array
    {
        $reel->load('translations');
        $target = strtolower(trim($targetLocale));
        $requestedSource = strtolower(trim((string) $sourceLocale));
        $defaultLocale = strtolower($this->languageRepository->defaultLocale());

        $sourceTranslation = null;
        $resolvedSourceLocale = '';

        if ($requestedSource !== '') {
            $t = $reel->translations->firstWhere('locale', $requestedSource);
            if ($t !== null && trim((string) $t->title) !== '') {
                $sourceTranslation = $t;
                $resolvedSourceLocale = $requestedSource;
            }
        }

        if ($sourceTranslation === null) {
            foreach (collect([$defaultLocale])->merge($reel->translations->pluck('locale'))->reject(static fn ($l) => (string) $l === $target)->unique() as $locale) {
                $t = $reel->translations->firstWhere('locale', (string) $locale);
                if ($t !== null && trim((string) $t->title) !== '') {
                    $sourceTranslation = $t;
                    $resolvedSourceLocale = (string) $locale;
                    break;
                }
            }
        }

        if ($sourceTranslation === null) {
            throw new RuntimeException(__('No source locale with content was found for AI generation.'));
        }

        $texts = [trim((string) $sourceTranslation->title), trim((string) $sourceTranslation->description)];
        $translated = $this->aiContentService->translateBatch($texts, $targetLocale, $resolvedSourceLocale);

        return [
            'source_locale' => $resolvedSourceLocale,
            'target_locale' => $targetLocale,
            'fields' => [
                'title' => trim($translated[0] ?? ''),
                'description' => trim($translated[1] ?? ''),
            ],
        ];
    }

    public function translateReelDraft(array $translations, string $targetLocale, ?string $sourceLocale = null): array
    {
        $target = strtolower(trim($targetLocale));
        $requestedSource = strtolower(trim((string) $sourceLocale));
        $defaultLocale = strtolower($this->languageRepository->defaultLocale());

        $normalized = collect($translations)
            ->mapWithKeys(static fn (array $t, $l): array => [strtolower((string) $l) => $t]);

        $sourceData = null;
        $resolvedSourceLocale = '';

        if ($requestedSource !== '' && $normalized->has($requestedSource)) {
            $data = (array) $normalized->get($requestedSource);
            if (trim((string) ($data['title'] ?? '')) !== '') {
                $sourceData = $data;
                $resolvedSourceLocale = $requestedSource;
            }
        }

        if ($sourceData === null) {
            foreach (collect([$defaultLocale])->merge($normalized->keys())->reject(static fn ($l) => (string) $l === $target)->unique() as $locale) {
                $data = $normalized->get((string) $locale);
                if (is_array($data) && trim((string) ($data['title'] ?? '')) !== '') {
                    $sourceData = $data;
                    $resolvedSourceLocale = (string) $locale;
                    break;
                }
            }
        }

        if ($sourceData === null) {
            throw new RuntimeException(__('No source locale with content was found for AI generation.'));
        }

        $texts = [trim((string) ($sourceData['title'] ?? '')), trim((string) ($sourceData['description'] ?? ''))];
        $translated = $this->aiContentService->translateBatch($texts, $targetLocale, $resolvedSourceLocale);

        return [
            'source_locale' => $resolvedSourceLocale,
            'target_locale' => $targetLocale,
            'fields' => [
                'title' => trim($translated[0] ?? ''),
                'description' => trim($translated[1] ?? ''),
            ],
        ];
    }

    private function translateModel(Model $model, string $scope, string $bodyField, string $targetLocale, ?string $sourceLocale = null, array $extraFieldNames = []): array
    {
        [$sourceTranslation, $resolvedSourceLocale, $targetLanguage] = $this->resolveTranslations($model, $targetLocale, $sourceLocale);
        $blockDefinitions = $this->blockDefinitionsForScope($scope);
        $sourceBlocks = $this->prepareBlocksForTranslation($sourceTranslation->blocks ?? new EloquentCollection, $blockDefinitions);

        $payload = [
            'name' => (string) ($sourceTranslation->title ?? ''),
            'body' => (string) ($sourceTranslation->{$bodyField} ?? ''),
            'blocks' => $sourceBlocks,
        ];

        foreach ($extraFieldNames as $fieldName) {
            $payload[$fieldName] = (string) ($sourceTranslation->{$fieldName} ?? '');
        }

        $translated = $this->translatePayload($payload, $targetLocale, $resolvedSourceLocale);
        $translatedBlocks = $this->mergeTranslatedBlocks($sourceBlocks, $translated['blocks']);

        $fields = [
            'name' => trim($translated['name']),
            'body' => trim($translated['body']),
        ];

        foreach ($extraFieldNames as $fieldName) {
            $fields[$fieldName] = trim((string) ($translated[$fieldName] ?? ''));
        }

        return [
            'source_locale' => $resolvedSourceLocale,
            'target_locale' => $targetLocale,
            'fields' => $fields,
            'blocks' => $translatedBlocks,
            'blocks_html' => (string) view('admin.partials.ai-block-cards', [
                'localeCode' => $targetLocale,
                'languageCode' => $targetLanguage['code'],
                'localeBlocks' => $translatedBlocks,
                'blockTypeEditors' => $blockDefinitions->all(),
            ])->render(),
        ];
    }

    private function generateSeoForModel(Model $model, string $scope, string $bodyField, string $targetLocale, ?string $sourceLocale = null): array
    {
        [$sourceTranslation, $resolvedSourceLocale, $targetLanguage] = $this->resolveTranslations($model, $targetLocale, $sourceLocale);
        $sourceLanguage = $this->localeMeta($resolvedSourceLocale);
        $blockDefinitions = $this->blockDefinitionsForScope($scope);
        $sourceBlocks = $this->prepareBlocksForTranslation($sourceTranslation->blocks ?? new EloquentCollection, $blockDefinitions);

        $payload = [
            'name' => (string) ($sourceTranslation->title ?? ''),
            'body' => (string) ($sourceTranslation->{$bodyField} ?? ''),
            'blocks' => $sourceBlocks,
        ];

        $response = $this->aiContentService->generateSeoJson($this->seoPrompt($payload, $sourceLanguage, $targetLanguage));

        $keywords = collect(data_get($response, 'keywords', []))
            ->map(static fn ($keyword): string => trim((string) $keyword))
            ->filter(static fn (string $keyword): bool => $keyword !== '')
            ->unique()
            ->values()
            ->all();

        return [
            'source_locale' => $resolvedSourceLocale,
            'target_locale' => $targetLocale,
            'seo' => [
                'meta_title' => trim((string) data_get($response, 'meta_title', '')),
                'meta_description' => trim((string) data_get($response, 'meta_description', '')),
                'keywords' => implode(', ', $keywords),
                'focus_keyword' => trim((string) data_get($response, 'focus_keyword', '')),
                'canonical_url' => '',
            ],
        ];
    }

    private function translateDraft(array $translations, string $scope, string $targetLocale, ?string $sourceLocale = null, array $extraFieldNames = []): array
    {
        [$sourceTranslation, $resolvedSourceLocale, $targetLanguage] = $this->resolveDraftTranslations($translations, $targetLocale, $sourceLocale);
        $blockDefinitions = $this->blockDefinitionsForScope($scope);
        $sourceBlocks = $this->prepareDraftBlocksForTranslation((array) ($sourceTranslation['blocks'] ?? []), $blockDefinitions);

        $payload = [
            'name' => (string) ($sourceTranslation['name'] ?? ''),
            'body' => (string) ($sourceTranslation['body'] ?? ''),
            'blocks' => $sourceBlocks,
        ];

        foreach ($extraFieldNames as $fieldName) {
            $payload[$fieldName] = (string) ($sourceTranslation[$fieldName] ?? '');
        }

        $translated = $this->translatePayload($payload, $targetLocale, $resolvedSourceLocale);
        $translatedBlocks = $this->mergeTranslatedBlocks($sourceBlocks, $translated['blocks']);

        $fields = [
            'name' => trim($translated['name']),
            'body' => trim($translated['body']),
        ];

        foreach ($extraFieldNames as $fieldName) {
            $fields[$fieldName] = trim((string) ($translated[$fieldName] ?? ''));
        }

        return [
            'source_locale' => $resolvedSourceLocale,
            'target_locale' => $targetLocale,
            'fields' => $fields,
            'blocks' => $translatedBlocks,
            'blocks_html' => (string) view('admin.partials.ai-block-cards', [
                'localeCode' => $targetLocale,
                'languageCode' => $targetLanguage['code'],
                'localeBlocks' => $translatedBlocks,
                'blockTypeEditors' => $blockDefinitions->all(),
            ])->render(),
        ];
    }

    private function generateSeoForDraft(array $translations, string $scope, string $targetLocale, ?string $sourceLocale = null): array
    {
        [$sourceTranslation, $resolvedSourceLocale, $targetLanguage] = $this->resolveDraftTranslations($translations, $targetLocale, $sourceLocale);
        $sourceLanguage = $this->localeMeta($resolvedSourceLocale);
        $blockDefinitions = $this->blockDefinitionsForScope($scope);
        $sourceBlocks = $this->prepareDraftBlocksForTranslation((array) ($sourceTranslation['blocks'] ?? []), $blockDefinitions);

        $payload = [
            'name' => (string) ($sourceTranslation['name'] ?? ''),
            'body' => (string) ($sourceTranslation['body'] ?? ''),
            'blocks' => $sourceBlocks,
        ];

        $response = $this->aiContentService->generateSeoJson($this->seoPrompt($payload, $sourceLanguage, $targetLanguage));

        $keywords = collect(data_get($response, 'keywords', []))
            ->map(static fn ($keyword): string => trim((string) $keyword))
            ->filter(static fn (string $keyword): bool => $keyword !== '')
            ->unique()
            ->values()
            ->all();

        return [
            'source_locale' => $resolvedSourceLocale,
            'target_locale' => $targetLocale,
            'seo' => [
                'meta_title' => trim((string) data_get($response, 'meta_title', '')),
                'meta_description' => trim((string) data_get($response, 'meta_description', '')),
                'keywords' => implode(', ', $keywords),
                'focus_keyword' => trim((string) data_get($response, 'focus_keyword', '')),
                'canonical_url' => '',
            ],
        ];
    }

    /**
     * @return array{0: object,1:string,2:array{code:string,name:string,english_name:string}}
     */
    private function resolveTranslations(Model $model, string $targetLocale, ?string $sourceLocale = null): array
    {
        $translations = $model->translations instanceof EloquentCollection ? $model->translations : new EloquentCollection;
        $target = strtolower(trim($targetLocale));
        $requestedSource = strtolower(trim((string) $sourceLocale));
        $defaultLocale = strtolower($this->languageRepository->defaultLocale());

        if ($requestedSource !== '') {
            $explicit = $translations->firstWhere('locale', $requestedSource);
            if ($explicit !== null && $this->translationHasContent($explicit)) {
                return [$explicit, $requestedSource, $this->localeMeta($target)];
            }
        }

        $candidateLocales = collect([$defaultLocale])
            ->merge(
                $translations->pluck('locale')
                    ->map(static fn ($locale): string => strtolower((string) $locale))
            )
            ->reject(static fn (string $locale): bool => $locale === $target)
            ->unique()
            ->values();

        foreach ($candidateLocales as $locale) {
            $translation = $translations->firstWhere('locale', $locale);
            if ($translation !== null && $this->translationHasContent($translation)) {
                return [$translation, (string) $locale, $this->localeMeta($target)];
            }
        }

        // Target locale has content but no other locale does — wrong direction
        $targetTranslation = $translations->firstWhere('locale', $target);
        if ($targetTranslation !== null && $this->translationHasContent($targetTranslation)) {
            $otherLocales = $translations->pluck('locale')
                ->reject(static fn ($l) => strtolower((string) $l) === $target)
                ->implode(', ');
            throw new RuntimeException(
                "'".$target."' tab-ს უკვე აქვს კონტენტი — ეს სორს ენაა. ".
                "სხვა ენების tab-ებზე ('".($otherLocales ?: '...')."') დააჭირე 'თარგმნე ენა' ღილაკს, ".
                "რომ '".$target."'-დან ითარგმნოს."
            );
        }

        throw new RuntimeException(__('No source locale with content was found for AI generation.'));
    }

    private function translationHasContent(object $translation): bool
    {
        $title = trim((string) ($translation->title ?? ''));
        $body = trim((string) ($translation->description ?? $translation->content ?? ''));

        if ($title !== '' || $body !== '') {
            return true;
        }

        foreach (($translation->blocks ?? []) as $block) {
            if ($this->blockDataHasContent((array) ($block->data ?? []))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: array<string,mixed>,1:string,2:array{code:string,name:string,english_name:string}}
     */
    private function resolveDraftTranslations(array $translations, string $targetLocale, ?string $sourceLocale = null): array
    {
        $normalized = collect($translations)
            ->filter(static fn ($translation): bool => is_array($translation))
            ->mapWithKeys(static fn (array $translation, $locale): array => [
                strtolower((string) $locale) => $translation,
            ]);

        $target = strtolower(trim($targetLocale));
        $requestedSource = strtolower(trim((string) $sourceLocale));
        $defaultLocale = strtolower($this->languageRepository->defaultLocale());

        if ($requestedSource !== '') {
            $explicit = $normalized->get($requestedSource);
            if (is_array($explicit) && $this->draftTranslationHasContent($explicit)) {
                return [$explicit, $requestedSource, $this->localeMeta($target)];
            }
        }

        $candidateLocales = collect([$defaultLocale])
            ->merge($normalized->keys())
            ->reject(static fn (string $locale): bool => $locale === $target)
            ->unique()
            ->values();

        foreach ($candidateLocales as $locale) {
            $translation = $normalized->get((string) $locale);
            if (is_array($translation) && $this->draftTranslationHasContent($translation)) {
                return [$translation, (string) $locale, $this->localeMeta($target)];
            }
        }

        // Target locale has content but no other locale does — wrong direction
        $targetDraft = $normalized->get($target);
        if (is_array($targetDraft) && $this->draftTranslationHasContent($targetDraft)) {
            $otherLocales = $normalized->keys()->reject(static fn ($l) => $l === $target)->implode(', ');
            throw new RuntimeException(
                "'".$target."' tab-ს უკვე აქვს კონტენტი — ეს სორს ენაა. ".
                "სხვა ენების tab-ებზე ('".($otherLocales ?: '...')."') დააჭირე 'თარგმნე ენა' ღილაკს, ".
                "რომ '".($target)."'-დან ითარგმნოს."
            );
        }

        throw new RuntimeException(__('No source locale with content was found for AI generation.'));
    }

    private function draftTranslationHasContent(array $translation): bool
    {
        $title = trim((string) ($translation['name'] ?? ''));
        $body = trim((string) ($translation['body'] ?? ''));

        if ($title !== '' || $body !== '') {
            return true;
        }

        foreach ((array) ($translation['blocks'] ?? []) as $block) {
            if (is_array($block) && $this->blockDataHasContent((array) ($block['data'] ?? []))) {
                return true;
            }
        }

        return false;
    }

    private function blockDataHasContent(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value) && $this->blockDataHasContent($value)) {
                return true;
            }

            if (! is_array($value) && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{code:string,name:string,english_name:string}
     */
    private function localeMeta(string $locale): array
    {
        $resolved = collect($this->languageRepository->getActiveLocales())
            ->first(static fn (array $item): bool => strtolower((string) $item['code']) === strtolower($locale));

        if (is_array($resolved)) {
            return [
                'code' => strtolower((string) $resolved['code']),
                'name' => (string) ($resolved['name'] ?: strtoupper((string) $resolved['code'])),
                'english_name' => (string) ($resolved['english_name'] ?: strtoupper((string) $resolved['code'])),
            ];
        }

        return [
            'code' => strtolower($locale),
            'name' => strtoupper($locale),
            'english_name' => strtoupper($locale),
        ];
    }

    private function blockDefinitionsForScope(string $scope): Collection
    {
        return $this->blockTypeRepository
            ->getEnabledForScope($scope)
            ->keyBy(static fn ($blockType): string => (string) $blockType->key)
            ->map(static fn ($blockType): array => [
                'key' => (string) $blockType->key,
                'label' => (string) $blockType->label,
                'icon' => (string) ($blockType->icon ?: 'bi-box'),
                'description' => $blockType->description !== null ? (string) $blockType->description : null,
                'default_data' => (array) ($blockType->default_data ?? []),
                'fields' => (array) data_get($blockType->schema, 'fields', []),
            ]);
    }

    private function prepareBlocksForTranslation(EloquentCollection $blocks, Collection $definitions): array
    {
        return $blocks
            ->sortBy('sort_order')
            ->values()
            ->map(function ($block, int $index) use ($definitions): array {
                $type = (string) ($block->type ?? '');
                $definition = (array) ($definitions->get($type) ?? []);

                return [
                    'instance_key' => sprintf('%s__%d', $type, $index),
                    'type' => $type,
                    'label' => (string) ($definition['label'] ?? $type),
                    'sort_order' => (int) ($block->sort_order ?? $index),
                    'data' => $this->normalizeBlockDataForAi((array) ($block->data ?? []), (array) ($definition['fields'] ?? [])),
                ];
            })
            ->all();
    }

    private function prepareDraftBlocksForTranslation(array $blocks, Collection $definitions): array
    {
        return collect($blocks)
            ->filter(static fn ($block): bool => is_array($block))
            ->sortBy(static fn (array $block, int $index): int => (int) ($block['sort_order'] ?? $index))
            ->values()
            ->map(function (array $block, int $index) use ($definitions): array {
                $type = (string) ($block['type'] ?? '');
                $definition = (array) ($definitions->get($type) ?? []);

                return [
                    'instance_key' => (string) ($block['instance_key'] ?? sprintf('%s__%d', $type, $index)),
                    'type' => $type,
                    'label' => (string) ($definition['label'] ?? $type),
                    'sort_order' => (int) ($block['sort_order'] ?? $index),
                    'data' => $this->normalizeBlockDataForAi((array) ($block['data'] ?? []), (array) ($definition['fields'] ?? [])),
                ];
            })
            ->all();
    }

    private function normalizeBlockDataForAi(array $data, array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $type = trim((string) ($field['type'] ?? 'text'));
            $value = $data[$key] ?? (($type === 'repeater') ? [] : '');

            if ($type === 'repeater') {
                $rows = collect(is_array($value) ? $value : [])
                    ->filter(static fn ($row): bool => is_array($row))
                    ->values()
                    ->map(fn (array $row): array => $this->normalizeBlockDataForAi($row, (array) ($field['fields'] ?? [])))
                    ->all();
                $normalized[$key] = $rows;

                continue;
            }

            $normalized[$key] = is_array($value) ? $value : (string) $value;
        }

        return $normalized;
    }

    private function mergeTranslatedBlocks(array $sourceBlocks, array $translatedBlocks): array
    {
        return collect($sourceBlocks)
            ->map(function (array $sourceBlock, int $index) use ($translatedBlocks): array {
                $candidate = $translatedBlocks[$index] ?? null;
                if (! is_array($candidate) || (string) ($candidate['type'] ?? '') !== (string) ($sourceBlock['type'] ?? '')) {
                    return $sourceBlock;
                }

                return [
                    'instance_key' => (string) ($sourceBlock['instance_key'] ?? sprintf('%s__%d', $sourceBlock['type'] ?? 'block', $index)),
                    'type' => (string) ($sourceBlock['type'] ?? ''),
                    'label' => (string) ($sourceBlock['label'] ?? ''),
                    'sort_order' => (int) ($sourceBlock['sort_order'] ?? $index),
                    'data' => is_array($candidate['data'] ?? null) ? $candidate['data'] : (array) ($sourceBlock['data'] ?? []),
                ];
            })
            ->all();
    }

    /**
     * Translate a structured payload (name, body, blocks[].data) via Google Translate.
     * Block metadata (type, instance_key, label, sort_order) is never translated.
     */
    private function translatePayload(array $payload, string $targetLocale, string $sourceLocale): array
    {
        // Collect all translatable strings with their dot-path keys
        $toTranslate = [];

        foreach ($payload as $key => $value) {
            if ($key !== 'blocks' && is_string($value) && $this->isTranslatableString($value)) {
                $toTranslate[$key] = $value;
            }
        }

        foreach ((array) ($payload['blocks'] ?? []) as $blockIdx => $block) {
            $dataStrings = $this->extractTranslatableStrings(
                (array) ($block['data'] ?? []),
                "blocks.{$blockIdx}.data"
            );
            $toTranslate += $dataStrings;
        }

        if ($toTranslate === []) {
            return $payload;
        }

        $paths = array_keys($toTranslate);
        $texts = array_values($toTranslate);
        $translated = $this->aiContentService->translateBatch($texts, $targetLocale, $sourceLocale);
        $translationMap = array_combine($paths, $translated);

        foreach ($payload as $key => $value) {
            if ($key !== 'blocks' && isset($translationMap[$key])) {
                $payload[$key] = $translationMap[$key];
            }
        }

        foreach ((array) ($payload['blocks'] ?? []) as $blockIdx => $block) {
            $payload['blocks'][$blockIdx]['data'] = $this->applyTranslatedStrings(
                (array) ($block['data'] ?? []),
                $translationMap,
                "blocks.{$blockIdx}.data"
            );
        }

        return $payload;
    }

    /**
     * Recursively collect translatable string values with their dot-path keys.
     *
     * @return array<string, string>
     */
    private function extractTranslatableStrings(array $data, string $prefix): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $path = "{$prefix}.{$key}";

            if (is_array($value)) {
                $result += $this->extractTranslatableStrings($value, $path);
            } elseif (is_string($value) && $this->isTranslatableString($value)) {
                $result[$path] = $value;
            }
        }

        return $result;
    }

    /**
     * Recursively apply translated strings back into a data array by dot-path.
     *
     * @param  array<string, string>  $translationMap
     */
    private function applyTranslatedStrings(array $data, array $translationMap, string $prefix): array
    {
        foreach ($data as $key => $value) {
            $path = "{$prefix}.{$key}";

            if (is_array($value)) {
                $data[$key] = $this->applyTranslatedStrings($value, $translationMap, $path);
            } elseif (isset($translationMap[$path])) {
                $data[$key] = $translationMap[$path];
            }
        }

        return $data;
    }

    /**
     * Returns true if the string contains natural-language text worth translating.
     * Skips URLs, Unix-style file paths, hex colors, and purely numeric values.
     */
    private function isTranslatableString(string $value): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return false;
        }

        if (preg_match('/^https?:\/\//i', $trimmed)) {
            return false;
        }

        if (preg_match('/^\/[^\s]*$/', $trimmed)) {
            return false;
        }

        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $trimmed)) {
            return false;
        }

        if (is_numeric($trimmed)) {
            return false;
        }

        return true;
    }

    private function seoPrompt(array $payload, array $sourceLanguage, array $targetLanguage): string
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You generate SEO metadata for a CMS page based on visible website content.
Use the provided content and generate SEO in {$targetLanguage['english_name']} ({$targetLanguage['code']}).
Source content language is {$sourceLanguage['english_name']} ({$sourceLanguage['code']}).

Return valid JSON only in this shape:
{
  "meta_title": "",
  "meta_description": "",
  "keywords": ["", ""],
  "focus_keyword": ""
}

Rules:
- meta_title max 60 characters when possible.
- meta_description max 160 characters when possible.
- keywords should be 5 to 8 short phrases.
- focus_keyword should be one clear primary phrase.
- Use the actual content, not generic filler.
- No markdown, no explanations.

Input JSON:
{$json}
PROMPT;
    }
}
