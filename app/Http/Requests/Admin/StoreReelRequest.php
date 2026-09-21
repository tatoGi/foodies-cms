<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Language;
use App\Models\ReelTranslation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'titles' => collect((array) $this->input('titles', []))
                ->map(static fn ($v): string => trim((string) $v))
                ->all(),
            'slugs' => collect((array) $this->input('slugs', []))
                ->map(static fn ($v): string => trim((string) $v))
                ->all(),
            'descriptions' => collect((array) $this->input('descriptions', []))
                ->map(static fn ($v): string => trim((string) $v))
                ->all(),
            'video_url' => trim((string) $this->input('video_url', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'titles' => ['required', 'array', 'min:1'],
            'titles.*' => ['nullable', 'string', 'max:255'],
            'slugs' => ['required', 'array', 'min:1'],
            'slugs.*' => ['nullable', 'string', 'max:255'],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'category' => ['required', 'in:sale,project,new'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $localeCodes = Language::query()
                ->pluck('code')
                ->map(static fn ($code): string => (string) $code)
                ->values();

            $titles = collect((array) $this->input('titles', []));
            $slugs = collect((array) $this->input('slugs', []));

            $hasAtLeastOne = false;

            foreach ($localeCodes as $locale) {
                $locale = (string) $locale;
                $title = trim((string) $titles->get($locale, ''));
                $slug = trim((string) $slugs->get($locale, ''));

                if ($title !== '' || $slug !== '') {
                    $hasAtLeastOne = true;
                }

                if ($title !== '' && $slug === '') {
                    $validator->errors()->add('slugs.'.$locale, __('Slug is required when title is provided.'));
                }

                if ($title === '' && $slug !== '') {
                    $validator->errors()->add('titles.'.$locale, __('Title is required when slug is provided.'));
                }

                if ($slug !== '') {
                    $exists = ReelTranslation::query()
                        ->where('slug', $slug)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('slugs.'.$locale, __('This slug is already used.'));
                    }
                }
            }

            if (! $hasAtLeastOne) {
                $validator->errors()->add('titles', __('At least one locale title and slug are required.'));
            }
        });
    }
}
