<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlockTypeDefinition;
use Illuminate\Http\UploadedFile;

class BlockNormalizationService
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
    ) {}

    /**
     * @param  array<string, mixed>  $submittedData
     * @param  array<string, mixed>  $removeFlags
     * @param  array<string, mixed>  $removeItems
     * @param  array<string, mixed>  $uploadedFiles
     * @param  array<string, mixed>  $existingData
     * @return array<string, mixed>
     */
    public function normalizeBlockData(
        array $submittedData,
        array $removeFlags,
        array $removeItems,
        array $uploadedFiles,
        BlockTypeDefinition $definition,
        array $existingData = []
    ): array {
        $fields = (array) data_get($definition->schema, 'fields', []);
        $normalized = [];

        foreach ($fields as $field) {
            $fieldKey = trim((string) ($field['key'] ?? ''));
            if ($fieldKey === '') {
                continue;
            }

            $fieldType = trim((string) ($field['type'] ?? 'text'));
            $uploaded = $uploadedFiles[$fieldKey] ?? null;
            $hasSubmittedValue = array_key_exists($fieldKey, $submittedData);
            $currentValue = $existingData[$fieldKey] ?? ($fieldType === 'gallery' ? [] : '');

            if ($fieldType === 'image' || $fieldType === 'video') {
                if ($uploaded instanceof UploadedFile) {
                    $normalized[$fieldKey] = $this->uploadBlockMediaFile($uploaded);

                    continue;
                }

                if ($this->isTruthy($removeFlags[$fieldKey] ?? null)) {
                    $normalized[$fieldKey] = '';

                    continue;
                }

                $rawMediaValue = $hasSubmittedValue ? $submittedData[$fieldKey] : $currentValue;
                $normalized[$fieldKey] = is_array($rawMediaValue) ? '' : trim((string) $rawMediaValue);

                continue;
            }

            if ($fieldType === 'gallery') {
                $dbPaths = $this->normalizeGalleryPaths($currentValue);
                $submittedPaths = $hasSubmittedValue
                    ? $this->normalizeGalleryPaths($submittedData[$fieldKey])
                    : null;
                $existingPaths = $submittedPaths ?? $dbPaths;

                if ($this->isTruthy($removeFlags[$fieldKey] ?? null)) {
                    $existingPaths = [];
                }

                $pathsToRemove = $this->normalizeGalleryPaths($removeItems[$fieldKey] ?? []);
                if (
                    $submittedPaths !== null &&
                    $submittedPaths === [] &&
                    $dbPaths !== [] &&
                    $pathsToRemove !== [] &&
                    ! $this->isTruthy($removeFlags[$fieldKey] ?? null)
                ) {
                    $existingPaths = $dbPaths;
                }

                if ($pathsToRemove !== []) {
                    $existingPaths = collect($existingPaths)
                        ->reject(static fn (string $path): bool => in_array($path, $pathsToRemove, true))
                        ->values()
                        ->all();
                }

                $storedPaths = [];
                if (is_array($uploaded)) {
                    $storedPaths = collect($uploaded)
                        ->filter(static fn ($file): bool => $file instanceof UploadedFile)
                        ->map(fn (UploadedFile $file): string => $this->uploadBlockMediaFile($file))
                        ->values()
                        ->all();
                }

                $normalized[$fieldKey] = collect(array_merge($existingPaths, $storedPaths))
                    ->unique()
                    ->values()
                    ->all();

                continue;
            }

            if ($fieldType === 'repeater') {
                $rows = $submittedData[$fieldKey] ?? [];
                $subFields = (array) ($field['fields'] ?? []);

                if (! is_array($rows)) {
                    $normalized[$fieldKey] = [];

                    continue;
                }

                $normalized[$fieldKey] = collect($rows)
                    ->filter(static fn ($row): bool => is_array($row))
                    ->map(function (array $row) use ($subFields): array {
                        $normalizedRow = [];

                        foreach ($subFields as $subField) {
                            $subFieldKey = trim((string) ($subField['key'] ?? ''));
                            if ($subFieldKey === '') {
                                continue;
                            }

                            $subFieldType = trim((string) ($subField['type'] ?? 'text'));
                            $subValue = $row[$subFieldKey] ?? null;

                            if ($subValue === null) {
                                $normalizedRow[$subFieldKey] = '';

                                continue;
                            }

                            if (is_array($subValue)) {
                                $normalizedRow[$subFieldKey] = $subValue;

                                continue;
                            }

                            $stringValue = (string) $subValue;

                            if ($subFieldType === 'number') {
                                $normalizedRow[$subFieldKey] = trim($stringValue) === '' ? '' : (float) $stringValue;

                                continue;
                            }

                            $normalizedRow[$subFieldKey] = $subFieldType === 'rich_text'
                                ? $stringValue
                                : trim($stringValue);
                        }

                        return $normalizedRow;
                    })
                    ->filter(static function (array $row): bool {
                        foreach ($row as $value) {
                            if (is_array($value) && $value !== []) {
                                return true;
                            }

                            if (! is_array($value) && trim((string) $value) !== '') {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->values()
                    ->all();

                continue;
            }

            $value = $submittedData[$fieldKey] ?? null;

            if (is_array($value)) {
                $normalized[$fieldKey] = $value;

                continue;
            }

            if ($value === null) {
                $normalized[$fieldKey] = '';

                continue;
            }

            $stringValue = (string) $value;
            if ($fieldType === 'number') {
                $normalized[$fieldKey] = trim($stringValue) === '' ? '' : (float) $stringValue;

                continue;
            }

            $normalized[$fieldKey] = $fieldType === 'rich_text' ? $stringValue : trim($stringValue);
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    public function normalizeGalleryPaths(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(static fn ($item): string => trim((string) $item))
                ->filter(static fn (string $item): bool => $item !== '')
                ->values()
                ->all();
        }

        return collect(preg_split('/[\r\n,]+/', (string) $value) ?: [])
            ->map(static fn ($item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    public function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    private function uploadBlockMediaFile(UploadedFile $file): string
    {
        $media = $this->mediaUploadService->uploadFile($file, [
            'user_id' => optional(auth('admin')->user())->id,
        ]);

        return (string) $media->path;
    }
}
