<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\BlockTypeDefinition;
use App\Models\Language;
use App\Models\ProductTranslation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'names' => collect((array) $this->input('names', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'categories' => collect((array) $this->input('categories', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'slugs' => collect((array) $this->input('slugs', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'meta_titles' => collect((array) $this->input('meta_titles', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'meta_descriptions' => collect((array) $this->input('meta_descriptions', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'keywords' => collect((array) $this->input('keywords', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'focus_keywords' => collect((array) $this->input('focus_keywords', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'canonical_urls' => collect((array) $this->input('canonical_urls', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'descriptions' => collect((array) $this->input('descriptions', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'excerpts' => collect((array) $this->input('excerpts', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'block_types' => collect((array) $this->input('block_types', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'colors' => collect((array) $this->input('colors', []))->filter(static fn ($v): bool => trim((string) $v) !== '')->values()->all(),
            'spec_dimensions' => trim((string) $this->input('spec_dimensions', '')),
            'spec_height' => trim((string) $this->input('spec_height', '')),
            'spec_material' => trim((string) $this->input('spec_material', '')),
            'spec_colors' => trim((string) $this->input('spec_colors', '')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = (int) $this->route('product')?->id;

        return [
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku,'.$productId],
            'brand' => ['nullable', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'on_sale' => ['nullable', 'boolean'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'show_in_reels' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'spec_dimensions' => ['nullable', 'string', 'max:255'],
            'spec_height' => ['nullable', 'string', 'max:255'],
            'spec_material' => ['nullable', 'string', 'max:255'],
            'spec_colors' => ['nullable', 'string', 'max:255'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'names' => ['required', 'array', 'min:1'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'string', 'max:100'],
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
            'excerpts' => ['nullable', 'array'],
            'excerpts.*' => ['nullable', 'string'],
            'product_category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
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
            $productId = (int) $this->route('product')?->id;

            $localeCodes = Language::query()
                ->pluck('code')
                ->map(static fn ($code): string => (string) $code)
                ->values();

            $names = collect((array) $this->input('names', []));
            $categories = collect((array) $this->input('categories', []));
            $slugs = collect((array) $this->input('slugs', []));
            $blocks = collect((array) $this->input('blocks', []));
            $blockTypes = collect((array) $this->input('block_types', []))
                ->map(static fn ($v): string => trim((string) $v))
                ->filter(static fn (string $v): bool => $v !== '')
                ->unique();

            $invalidNameLocales = $names->keys()->filter(static fn ($k): bool => ! $localeCodes->contains((string) $k));
            if ($invalidNameLocales->isNotEmpty()) {
                $validator->errors()->add('names', __('Invalid locale keys were submitted.'));
            }

            $invalidSlugLocales = $slugs->keys()->filter(static fn ($k): bool => ! $localeCodes->contains((string) $k));
            if ($invalidSlugLocales->isNotEmpty()) {
                $validator->errors()->add('slugs', __('Invalid locale keys were submitted.'));
            }

            $invalidCategoryLocales = $categories->keys()->filter(static fn ($k): bool => ! $localeCodes->contains((string) $k));
            if ($invalidCategoryLocales->isNotEmpty()) {
                $validator->errors()->add('categories', __('Invalid locale keys were submitted.'));
            }

            $invalidBlockLocales = $blocks->keys()->filter(static fn ($k): bool => ! $localeCodes->contains((string) $k));
            if ($invalidBlockLocales->isNotEmpty()) {
                $validator->errors()->add('blocks', __('Invalid locale keys were submitted.'));
            }

            if ($blockTypes->isNotEmpty()) {
                $allowedBlockTypes = BlockTypeDefinition::query()
                    ->where('scope', 'product')
                    ->where('is_enabled', true)
                    ->pluck('key')
                    ->map(static fn ($v): string => (string) $v);

                $invalidBlockTypes = $blockTypes->filter(static fn (string $k): bool => ! $allowedBlockTypes->contains($k));
                if ($invalidBlockTypes->isNotEmpty()) {
                    $validator->errors()->add('block_types', __('Invalid block types were selected.'));
                }
            }

            $hasAtLeastOne = false;
            foreach ($localeCodes as $locale) {
                $locale = (string) $locale;
                $name = trim((string) $names->get($locale, ''));
                $slug = trim((string) $slugs->get($locale, ''));

                if ($name !== '' || $slug !== '') {
                    $hasAtLeastOne = true;
                }

                if ($name !== '' && $slug === '') {
                    $validator->errors()->add('slugs.'.$locale, __('Slug is required when name is provided.'));
                }

                if ($name === '' && $slug !== '') {
                    $validator->errors()->add('names.'.$locale, __('Name is required when slug is provided.'));
                }

                if ($slug !== '') {
                    $exists = ProductTranslation::query()
                        ->where('slug', $slug)
                        ->whereDoesntHave('product', fn ($q) => $q->where('id', $productId))
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
