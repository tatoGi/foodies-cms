<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlockTypeDefinition;
use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BlockTypeService
{
    public function __construct(
        private readonly BlockTypeRepositoryInterface $blockTypeRepository,
        private readonly LanguageRepositoryInterface $languageRepository,
    ) {}

    /**
     * @return array{blockTypes:Collection<int, BlockTypeDefinition>,currentLocale:string}
     */
    public function indexViewData(): array
    {
        return [
            'blockTypes' => $this->blockTypeRepository->allForAdminList(),
            'currentLocale' => app()->getLocale(),
        ];
    }

    /**
     * @return array{locales:array<int, array{code:string,name:string,flag:string,is_default:bool}>,defaultLocale:string,selectedLocaleCodes:array<int, string>,availableBlockTypeOptions:array<int, array{key:string,label:string,scope:string,icon:string,description:?string}>}
     */
    public function createViewData(): array
    {
        $locales = $this->languageRepository->getActiveLocales();
        $selectedLocaleCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $existingKeys = $this->blockTypeRepository->allForAdminList()
            ->pluck('key')
            ->map(static fn ($key): string => (string) $key)
            ->all();

        return [
            'locales' => $locales,
            'defaultLocale' => $this->defaultLocaleCode($locales),
            'selectedLocaleCodes' => $selectedLocaleCodes,
            'availableBlockTypeOptions' => $this->availableBlockTypeOptions($existingKeys),
        ];
    }

    /**
     * @return array{blockType:BlockTypeDefinition,locales:array<int, array{code:string,name:string,flag:string,is_default:bool}>,defaultLocale:string,selectedLocaleCodes:array<int, string>}
     */
    public function editViewData(int|string $id): array
    {
        $blockType = $this->blockTypeRepository->findOrFail($id);
        $locales = $this->languageRepository->getActiveLocales();
        $selectedLocaleCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();

        return [
            'blockType' => $blockType,
            'locales' => $locales,
            'defaultLocale' => $this->defaultLocaleCode($locales),
            'selectedLocaleCodes' => $selectedLocaleCodes,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): void
    {
        $definition = $this->canonicalDefinition((string) $validated['key']);
        $locales = $this->languageRepository->getActiveLocales();
        $localeCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $translatedLabels = $this->normalizeLocaleTextMap((array) ($validated['labels'] ?? []), $localeCodes);
        $this->ensureLabelsExist($translatedLabels);
        $translatedDescriptions = $this->normalizeLocaleTextMap((array) ($validated['descriptions'] ?? []), $localeCodes);
        $schema = [
            'fields' => $this->normalizeFields((array) ($validated['fields'] ?? []), $localeCodes, $defaultLocale),
            'translations' => [
                'labels' => $translatedLabels,
                'descriptions' => $translatedDescriptions,
            ],
        ];

        $defaultData = [];
        foreach ($schema['fields'] as $field) {
            $fieldKey = (string) ($field['key'] ?? '');
            if ($fieldKey === '') {
                continue;
            }

            $defaultData[$fieldKey] = $field['default'] ?? '';
        }

        $this->blockTypeRepository->create([
            'key' => (string) $definition['key'],
            'label' => $this->resolvePrimaryLocaleText($translatedLabels, $defaultLocale),
            'scope' => (string) ($definition['scope'] ?? 'page'),
            'description' => $this->resolvePrimaryLocaleText($translatedDescriptions, $defaultLocale, true),
            'icon' => (string) ($validated['icon'] ?? ($definition['icon'] ?? 'bi-box')),
            'schema' => $schema,
            'default_data' => $defaultData,
            'sort_order' => (int) ($validated['sort_order'] ?? ($this->blockTypeRepository->maxSortOrder() + 1)),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'is_system' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(int|string $id, array $validated): void
    {
        $blockType = $this->blockTypeRepository->findOrFail($id);
        $isSystem = (bool) ($blockType->is_system ?? false);
        $locales = $this->languageRepository->getActiveLocales();
        $localeCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $translatedLabels = $this->normalizeLocaleTextMap((array) ($validated['labels'] ?? []), $localeCodes);
        $this->ensureLabelsExist($translatedLabels);
        $translatedDescriptions = $this->normalizeLocaleTextMap((array) ($validated['descriptions'] ?? []), $localeCodes);
        $schema = [
            'fields' => $this->normalizeFields((array) ($validated['fields'] ?? []), $localeCodes, $defaultLocale),
            'translations' => [
                'labels' => $translatedLabels,
                'descriptions' => $translatedDescriptions,
            ],
        ];

        $defaultData = collect((array) ($blockType->default_data ?? []))
            ->only(collect($schema['fields'])->pluck('key')->filter()->all())
            ->all();

        foreach ($schema['fields'] as $field) {
            $fieldKey = (string) ($field['key'] ?? '');
            if ($fieldKey !== '' && ! array_key_exists($fieldKey, $defaultData)) {
                $defaultData[$fieldKey] = $field['default'] ?? '';
            }
        }

        $this->blockTypeRepository->update($blockType, [
            'key' => $isSystem ? (string) $blockType->key : (string) $validated['key'],
            'label' => $this->resolvePrimaryLocaleText($translatedLabels, $defaultLocale),
            'scope' => $isSystem ? (string) $blockType->scope : (string) $validated['scope'],
            'description' => $this->resolvePrimaryLocaleText($translatedDescriptions, $defaultLocale, true),
            'icon' => $validated['icon'] ?? null,
            'schema' => $schema,
            'default_data' => $defaultData,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
        ]);
    }

    public function delete(int|string $id): bool
    {
        $blockType = $this->blockTypeRepository->findOrFail($id);

        return $this->blockTypeRepository->delete($blockType);
    }

    /**
     * @param  array<string, string>  $translatedLabels
     */
    private function ensureLabelsExist(array $translatedLabels): void
    {
        if ($translatedLabels === []) {
            throw ValidationException::withMessages([
                'labels' => __('At least one label is required.'),
            ]);
        }
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
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $allowedLocales
     * @return array<string, string>
     */
    private function normalizeLocaleTextMap(array $values, array $allowedLocales): array
    {
        $normalized = [];
        foreach ($allowedLocales as $locale) {
            $value = trim((string) ($values[$locale] ?? ''));
            if ($value !== '') {
                $normalized[$locale] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function resolvePrimaryLocaleText(array $values, string $defaultLocale, bool $nullable = false): ?string
    {
        $defaultValue = trim((string) ($values[$defaultLocale] ?? ''));
        if ($defaultValue !== '') {
            return $defaultValue;
        }

        $firstValue = trim((string) (collect($values)->first() ?? ''));
        if ($firstValue !== '') {
            return $firstValue;
        }

        return $nullable ? null : '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<int, string>  $localeCodes
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFields(array $fields, array $localeCodes, string $defaultLocale): array
    {
        return collect($fields)
            ->values()
            ->map(function (array $field) use ($localeCodes, $defaultLocale): array {
                $fieldKey = trim((string) ($field['key'] ?? ''));
                $fieldType = trim((string) ($field['type'] ?? 'text'));
                $labels = $this->normalizeLocaleTextMap((array) ($field['labels'] ?? []), $localeCodes);
                $legacyLabel = trim((string) ($field['label'] ?? ''));
                if ($legacyLabel !== '' && ! isset($labels[$defaultLocale])) {
                    $labels[$defaultLocale] = $legacyLabel;
                }

                if ($labels === [] && $fieldKey !== '') {
                    $labels[$defaultLocale] = str_replace('_', ' ', ucfirst($fieldKey));
                }

                $helps = $this->normalizeLocaleTextMap((array) ($field['helps'] ?? []), $localeCodes);
                $legacyHelp = trim((string) ($field['help'] ?? ''));
                if ($legacyHelp !== '' && ! isset($helps[$defaultLocale])) {
                    $helps[$defaultLocale] = $legacyHelp;
                }

                $normalized = [
                    'label' => $this->resolvePrimaryLocaleText($labels, $defaultLocale) ?? '',
                    'labels' => $labels,
                    'key' => $fieldKey,
                    'type' => $fieldType,
                    'help' => $this->resolvePrimaryLocaleText($helps, $defaultLocale, true),
                    'helps' => $helps,
                ];

                // Handle repeater-specific fields
                if ($fieldType === 'repeater') {
                    $addButtonLabels = $this->normalizeLocaleTextMap((array) ($field['add_button_labels'] ?? []), $localeCodes);
                    $legacyAddButtonLabel = trim((string) ($field['add_button_label'] ?? ''));
                    if ($legacyAddButtonLabel !== '' && ! isset($addButtonLabels[$defaultLocale])) {
                        $addButtonLabels[$defaultLocale] = $legacyAddButtonLabel;
                    }

                    if ($addButtonLabels === []) {
                        $addButtonLabels[$defaultLocale] = __('Add item');
                    }

                    $normalized['add_button_label'] = $this->resolvePrimaryLocaleText($addButtonLabels, $defaultLocale) ?? __('Add item');
                    $normalized['add_button_labels'] = $addButtonLabels;

                    // Recursively normalize nested sub-fields
                    $subFields = (array) ($field['fields'] ?? []);
                    if (! empty($subFields)) {
                        $normalized['fields'] = $this->normalizeFields($subFields, $localeCodes, $defaultLocale);
                    } else {
                        $normalized['fields'] = [];
                    }
                }

                return $normalized;
            })
            ->filter(static fn (array $field): bool => $field['key'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $existingKeys
     * @return array<int, array{key:string,label:string,scope:string,icon:string,description:?string}>
     */
    private function availableBlockTypeOptions(array $existingKeys): array
    {
        return collect((array) config('cms_blocks.definitions', []))
            ->filter(static fn ($definition): bool => is_array($definition))
            ->map(function (array $definition): array {
                $labels = (array) ($definition['labels'] ?? []);
                $descriptions = (array) ($definition['descriptions'] ?? []);

                return [
                    'key' => (string) ($definition['key'] ?? ''),
                    'label' => (string) ($labels['en'] ?? ($definition['key'] ?? '')),
                    'scope' => (string) ($definition['scope'] ?? 'page'),
                    'icon' => (string) ($definition['icon'] ?? 'bi-box'),
                    'description' => ($descriptions['en'] ?? null) ?: null,
                ];
            })
            ->filter(static fn (array $definition): bool => $definition['key'] !== '' && ! in_array($definition['key'], $existingKeys, true))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function canonicalDefinition(string $key): array
    {
        $definition = collect((array) config('cms_blocks.definitions', []))
            ->first(static fn ($definition): bool => is_array($definition) && (string) ($definition['key'] ?? '') === $key);

        if (! is_array($definition)) {
            throw ValidationException::withMessages([
                'key' => __('Selected block type is not available.'),
            ]);
        }

        return $definition;
    }
}
