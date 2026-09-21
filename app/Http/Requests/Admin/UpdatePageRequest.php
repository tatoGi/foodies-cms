<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\BlockTypeDefinition;
use App\Models\Language;
use App\Models\PageTranslation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePageRequest extends FormRequest
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
            'template' => ['required', 'string', 'exists:page_templates,slug'],
            'parent_id' => ['nullable', 'integer', 'exists:pages,id'],
            'is_home' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published' => ['nullable', 'boolean'],
            'feature_image' => ['nullable', 'string', 'max:500'],
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
            'post_ids' => ['nullable', 'array'],
            'post_ids.*' => ['integer', 'distinct', 'exists:posts,id'],
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

            $page = $this->route('page');
            $pageId = is_object($page) ? (int) ($page->id ?? 0) : (int) $page;
            $names = collect((array) $this->input('names', []));
            $slugs = collect((array) $this->input('slugs', []))
                ->map(static fn ($value): string => trim((string) $value));
            $blocks = collect((array) $this->input('blocks', []));
            $blockTypes = collect((array) $this->input('block_types', []))
                ->map(static fn ($value): string => trim((string) $value))
                ->filter(static fn (string $value): bool => $value !== '')
                ->unique();
            $parentId = (int) $this->input('parent_id', 0);
            $isHome = $this->boolean('is_home');

            if ($parentId !== 0 && $parentId === $pageId) {
                $validator->errors()->add('parent_id', __('Parent page cannot be the same page.'));
            }

            if ($isHome && $parentId > 0) {
                $validator->errors()->add('parent_id', __('Homepage cannot have a parent page.'));
            }

            $invalidNameLocales = $names->keys()->filter(
                static fn ($key): bool => ! $localeCodes->contains((string) $key)
            );
            if ($invalidNameLocales->isNotEmpty()) {
                $validator->errors()->add('names', __('Invalid locale keys were submitted.'));
            }

            $invalidBlockLocales = $blocks->keys()->filter(
                static fn ($key): bool => ! $localeCodes->contains((string) $key)
            );
            if ($invalidBlockLocales->isNotEmpty()) {
                $validator->errors()->add('blocks', __('Invalid locale keys were submitted.'));
            }

            if ($blockTypes->isNotEmpty()) {
                $allowedBlockTypes = BlockTypeDefinition::query()
                    ->where('scope', 'page')
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

            if ($pageId > 0) {
                foreach ($slugs as $locale => $slug) {
                    if ($slug === '') {
                        continue;
                    }

                    $exists = PageTranslation::query()
                        ->where('slug', $slug)
                        ->where('page_id', '!=', $pageId)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('slugs.'.$locale, __('This slug is already used.'));
                    }
                }
            }

            $hasAtLeastOneName = $names->map(static fn ($v): string => trim((string) $v))
                ->filter(static fn (string $v): bool => $v !== '')
                ->isNotEmpty();

            if (! $hasAtLeastOneName) {
                $validator->errors()->add('names', __('At least one locale name is required.'));
            }
        });
    }
}
