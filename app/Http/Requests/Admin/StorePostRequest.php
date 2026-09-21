<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\BlockTypeDefinition;
use App\Models\Language;
use App\Models\PostTranslation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePostRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'names' => collect((array) $this->input('names', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
            'slugs' => collect((array) $this->input('slugs', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
            'meta_titles' => collect((array) $this->input('meta_titles', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
            'meta_descriptions' => collect((array) $this->input('meta_descriptions', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
            'keywords' => collect((array) $this->input('keywords', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
            'focus_keywords' => collect((array) $this->input('focus_keywords', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
            'canonical_urls' => collect((array) $this->input('canonical_urls', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
            'descriptions' => collect((array) $this->input('descriptions', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
            'block_types' => collect((array) $this->input('block_types', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->all(),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:50'],
            'feature_image' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'published' => ['nullable', 'boolean'],
            'names' => ['required', 'array', 'min:1'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'slugs' => ['required', 'array', 'min:1'],
            'slugs.*' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u'],
            'meta_titles' => ['nullable', 'array'],
            'meta_titles.*' => ['nullable', 'string', 'max:255'],
            'meta_descriptions' => ['nullable', 'array'],
            'meta_descriptions.*' => ['nullable', 'string'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['nullable', 'string'],
            'focus_keywords' => ['nullable', 'array'],
            'focus_keywords.*' => ['nullable', 'string', 'max:255'],
            'canonical_urls' => ['nullable', 'array'],
            'canonical_urls.*' => ['nullable', 'url', 'max:500'],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string'],
            'block_types' => ['nullable', 'array'],
            'block_types.*' => ['nullable', 'string', 'max:50'],
            'page_ids' => ['nullable', 'array'],
            'page_ids.*' => ['integer', 'distinct', 'exists:pages,id'],
            'blocks' => ['nullable', 'array'],
            'blocks.*' => ['nullable', 'array'],
            'blocks.*.*' => ['nullable', 'array'],
            'blocks.*.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'blocks.*.*.data' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $localeCodes = Language::query()
                ->pluck('code')
                ->map(static fn ($code): string => (string) $code)
                ->values();

            $names = collect((array) $this->input('names', []));
            $slugs = collect((array) $this->input('slugs', []));
            $metaTitles = collect((array) $this->input('meta_titles', []));
            $metaDescriptions = collect((array) $this->input('meta_descriptions', []));
            $keywords = collect((array) $this->input('keywords', []));
            $focusKeywords = collect((array) $this->input('focus_keywords', []));
            $canonicalUrls = collect((array) $this->input('canonical_urls', []));
            $blockTypes = collect((array) $this->input('block_types', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->filter(static fn (string $value): bool => $value !== '')
                ->unique();
            $blocks = collect((array) $this->input('blocks', []));

            $invalidNameLocales = $names->keys()->filter(
                static fn ($key): bool => ! $localeCodes->contains((string) $key)
            );
            if ($invalidNameLocales->isNotEmpty()) {
                $validator->errors()->add('names', __('Invalid locale keys were submitted.'));
            }

            $invalidSlugLocales = $slugs->keys()->filter(
                static fn ($key): bool => ! $localeCodes->contains((string) $key)
            );
            if ($invalidSlugLocales->isNotEmpty()) {
                $validator->errors()->add('slugs', __('Invalid locale keys were submitted.'));
            }

            $invalidBlockLocales = $blocks->keys()->filter(
                static fn ($key): bool => ! $localeCodes->contains((string) $key)
            );
            if ($invalidBlockLocales->isNotEmpty()) {
                $validator->errors()->add('blocks', __('Invalid locale keys were submitted.'));
            }

            if ($blockTypes->isNotEmpty()) {
                $allowedBlockTypes = BlockTypeDefinition::query()
                    ->where('scope', 'post')
                    ->where('is_enabled', true)
                    ->pluck('key')
                    ->map(static fn ($value): string => (string) $value);

                $invalidBlockTypes = $blockTypes->filter(
                    static fn (string $key): bool => ! $allowedBlockTypes->contains($key)
                );

                if ($invalidBlockTypes->isNotEmpty()) {
                    $validator->errors()->add('block_types', __('Invalid block types were selected.'));
                }
            }

            $hasAtLeastOne = false;
            foreach ($localeCodes as $locale) {
                $locale = (string) $locale;
                $name = trim((string) $names->get($locale, ''));
                $slug = trim((string) $slugs->get($locale, ''));
                $metaTitle = trim((string) $metaTitles->get($locale, ''));
                $metaDescription = trim((string) $metaDescriptions->get($locale, ''));
                $keyword = trim((string) $keywords->get($locale, ''));
                $focusKeyword = trim((string) $focusKeywords->get($locale, ''));
                $canonicalUrl = trim((string) $canonicalUrls->get($locale, ''));
                $hasSeo = $metaTitle !== '' || $metaDescription !== '' || $keyword !== '' || $focusKeyword !== '' || $canonicalUrl !== '';

                if ($name !== '' || $slug !== '' || $hasSeo) {
                    $hasAtLeastOne = true;
                }

                if ($name !== '' && $slug === '') {
                    $validator->errors()->add('slugs.'.$locale, __('Slug is required when name is provided.'));
                }

                if ($name === '' && $slug !== '') {
                    $validator->errors()->add('names.'.$locale, __('Name is required when slug is provided.'));
                }

                if ($hasSeo && ($name === '' || $slug === '')) {
                    $validator->errors()->add('names.'.$locale, __('Name and slug are required when SEO data is provided.'));
                }

                if ($slug !== '') {
                    $exists = PostTranslation::query()
                        ->where('slug', $slug)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('slugs.'.$locale, __('This slug is already used.'));
                    }
                }
            }

            if (! $hasAtLeastOne) {
                $validator->errors()->add('names', __('At least one locale name and slug are required.'));
            }
        });
    }
}
