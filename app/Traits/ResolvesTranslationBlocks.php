<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Collection;

trait ResolvesTranslationBlocks
{
    /**
     * Picks the best translation: preferred locale → fallback locale → first available.
     * Works on any model that exposes a ->translations Eloquent collection
     * where each item has a ->locale string and a ->blocks collection.
     */
    private function resolveTranslation(mixed $model, string $locale, string $fallbackLocale): mixed
    {
        $byLocale = $model->translations->firstWhere('locale', $locale);
        if ($byLocale !== null) {
            return $byLocale;
        }

        $byFallback = $model->translations->firstWhere('locale', $fallbackLocale);
        if ($byFallback !== null) {
            return $byFallback;
        }

        return $model->translations->first();
    }

    /**
     * Resolves a content-block's data array for the given block type.
     * Tries the resolved translation first, then falls back across all translations.
     *
     * @return array<string, mixed>
     */
    private function resolveBlockData(mixed $model, string $type, string $locale, string $fallbackLocale): array
    {
        $translation = $this->resolveTranslation($model, $locale, $fallbackLocale);
        if ($translation !== null) {
            $block = $translation->blocks->firstWhere('type', $type);
            if ($block !== null && $this->hasContent((array) ($block->data ?? []))) {
                return (array) $block->data;
            }
        }

        foreach ($model->translations as $item) {
            $block = $item->blocks->firstWhere('type', $type);
            if ($block !== null && $this->hasContent((array) ($block->data ?? []))) {
                return (array) $block->data;
            }
        }

        return [];
    }

    /**
     * @return Collection<int, mixed>
     */
    private function resolveLocalizedBlocks(mixed $model, string $locale, string $fallbackLocale): Collection
    {
        $translation = $this->resolveTranslation($model, $locale, $fallbackLocale);
        if ($translation === null) {
            return collect();
        }

        $translations = collect($model->translations);
        $orderedTranslations = $translations
            ->sortBy(function ($item) use ($locale, $fallbackLocale): int {
                $itemLocale = (string) ($item->locale ?? '');

                return match ($itemLocale) {
                    $locale => 0,
                    $fallbackLocale => 1,
                    default => 2,
                };
            })
            ->values();

        $baseBlocks = collect($translation->blocks ?? [])
            ->sortBy('sort_order')
            ->values();

        if ($baseBlocks->isEmpty()) {
            $fallbackTranslation = $orderedTranslations
                ->first(static fn ($item) => collect($item->blocks ?? [])->isNotEmpty());

            return collect($fallbackTranslation?->blocks ?? [])
                ->sortBy('sort_order')
                ->values();
        }

        $typeOffsets = [];

        return $baseBlocks->map(function ($block) use ($orderedTranslations, &$typeOffsets) {
            $type = (string) ($block->type ?? '');
            $occurrence = $typeOffsets[$type] ?? 0;
            $typeOffsets[$type] = $occurrence + 1;

            if ($this->hasContent((array) ($block->data ?? []))) {
                return $block;
            }

            foreach ($orderedTranslations as $translationCandidate) {
                $candidate = collect($translationCandidate->blocks ?? [])
                    ->sortBy('sort_order')
                    ->values()
                    ->filter(static fn ($item): bool => (string) ($item->type ?? '') === $type)
                    ->values()
                    ->get($occurrence);

                if ($candidate !== null && $this->hasContent((array) ($candidate->data ?? []))) {
                    $cloned = clone $block;
                    $cloned->data = (array) ($candidate->data ?? []);

                    return $cloned;
                }
            }

            return $block;
        })->values();
    }

    /**
     * Returns true if the data array contains at least one non-empty value.
     *
     * @param  array<string, mixed>  $data
     */
    private function hasContent(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value) && $value !== []) {
                return true;
            }

            if (! is_array($value) && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}
