<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlockTypeDefinition;
use Illuminate\Support\Collection;

class BlockTypeSyncService
{
    /**
     * @return array{created:int,updated:int}
     */
    public function syncSystemBlocks(bool $seedFieldsOnCreate = true, bool $createMissing = false): array
    {
        $definitions = collect((array) config('cms_blocks.definitions', []));
        $existingByKey = BlockTypeDefinition::query()->get()->keyBy('key');

        $created = 0;
        $updated = 0;

        foreach ($definitions as $index => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $key = trim((string) ($definition['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            /** @var BlockTypeDefinition|null $existing */
            $existing = $existingByKey->get($key);
            if ($existing instanceof BlockTypeDefinition) {
                $payload = $this->buildPayload($definition, $existing, $index, $seedFieldsOnCreate);
                $existing->fill($payload)->save();
                $updated++;

                continue;
            }

            if (! $createMissing) {
                continue;
            }

            $payload = $this->buildPayload($definition, $existing, $index, $seedFieldsOnCreate);
            BlockTypeDefinition::query()->create($payload);
            $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function buildPayload(array $definition, ?BlockTypeDefinition $existing, int $index, bool $seedFieldsOnCreate): array
    {
        $labels = $this->mergeLocaleText(
            (array) ($definition['labels'] ?? []),
            (array) data_get($existing?->schema, 'translations.labels', []),
        );
        $descriptions = $this->mergeLocaleText(
            (array) ($definition['descriptions'] ?? []),
            (array) data_get($existing?->schema, 'translations.descriptions', []),
        );
        $existingFields = (array) data_get($existing?->schema, 'fields', []);
        $fields = $this->mergeFields(
            (array) ($definition['fields'] ?? []),
            collect($existingFields)->keyBy('key'),
        );
        $defaultLocale = $this->defaultLocaleCode($labels);

        return [
            'key' => (string) $definition['key'],
            'label' => $labels[$defaultLocale] ?? collect($labels)->first() ?? (string) $definition['key'],
            'description' => $descriptions[$defaultLocale] ?? collect($descriptions)->first() ?: null,
            'scope' => (string) ($definition['scope'] ?? 'page'),
            'icon' => $existing?->icon ?: ((string) ($definition['icon'] ?? 'bi-box')),
            'schema' => [
                'fields' => $fields,
                'translations' => [
                    'labels' => $labels,
                    'descriptions' => $descriptions,
                ],
            ],
            'default_data' => $this->defaultData($fields),
            'is_enabled' => $existing?->is_enabled ?? (bool) ($definition['is_enabled'] ?? true),
            'sort_order' => $existing?->sort_order ?? (int) ($definition['sort_order'] ?? ($index + 1)),
            'is_system' => true,
        ];
    }

    /**
     * @param  array<string, string>  $default
     * @param  array<string, string>  $existing
     * @return array<string, string>
     */
    private function mergeLocaleText(array $default, array $existing): array
    {
        $merged = [];

        foreach ($default as $locale => $value) {
            $text = trim((string) $value);
            if ($text !== '') {
                $merged[(string) $locale] = $text;
            }
        }

        foreach ($existing as $locale => $value) {
            $text = trim((string) $value);
            if ($text !== '') {
                $merged[(string) $locale] = $text;
            }
        }

        return $merged;
    }

    /**
     * @param  array<int, array<string, mixed>>  $defaultFields
     * @param  Collection<string, array<string, mixed>>  $existingFields
     * @return array<int, array<string, mixed>>
     */
    private function mergeFields(array $defaultFields, Collection $existingFields): array
    {
        return collect($defaultFields)
            ->map(function (array $field) use ($existingFields): array {
                $fieldKey = trim((string) ($field['key'] ?? ''));
                $existing = (array) $existingFields->get($fieldKey, []);
                $labels = $this->mergeLocaleText(
                    (array) ($field['labels'] ?? []),
                    (array) ($existing['labels'] ?? []),
                );
                $helps = $this->mergeLocaleText(
                    (array) ($field['helps'] ?? []),
                    (array) ($existing['helps'] ?? []),
                );

                $mergedField = [
                    'key' => $fieldKey,
                    'type' => trim((string) ($field['type'] ?? 'text')),
                    'label' => $labels['en'] ?? collect($labels)->first() ?? (string) ($field['label'] ?? $fieldKey),
                    'labels' => $labels,
                    'help' => $helps['en'] ?? collect($helps)->first() ?? (string) ($field['help'] ?? ''),
                    'helps' => $helps,
                    'default' => $existing['default'] ?? ($field['default'] ?? ''),
                ];

                if (array_key_exists('options', $field) && is_array($field['options'])) {
                    $mergedField['options'] = $field['options'];
                } elseif (array_key_exists('options', $existing) && is_array($existing['options'])) {
                    $mergedField['options'] = $existing['options'];
                }

                if (($mergedField['type'] ?? '') === 'repeater') {
                    $existingNestedFields = collect((array) ($existing['fields'] ?? []))->keyBy('key');
                    $mergedField['fields'] = $this->mergeFields((array) ($field['fields'] ?? []), $existingNestedFields);
                    $mergedField['add_button_label'] = (string) (
                        $field['add_button_label']
                        ?? $existing['add_button_label']
                        ?? 'Add item'
                    );
                    $mergedField['add_button_labels'] = $this->mergeLocaleText(
                        (array) ($field['add_button_labels'] ?? []),
                        (array) ($existing['add_button_labels'] ?? []),
                    );
                }

                return $mergedField;
            })
            ->filter(static fn (array $field): bool => $field['key'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function defaultData(array $fields): array
    {
        $defaults = [];

        foreach ($fields as $field) {
            $fieldKey = trim((string) ($field['key'] ?? ''));
            if ($fieldKey === '') {
                continue;
            }

            $defaults[$fieldKey] = $field['default'] ?? '';
        }

        return $defaults;
    }

    /**
     * @param  array<string, string>  $labels
     */
    private function defaultLocaleCode(array $labels): string
    {
        if (isset($labels['en'])) {
            return 'en';
        }

        return (string) array_key_first($labels);
    }
}
