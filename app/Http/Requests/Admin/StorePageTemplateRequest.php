<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Language;
use App\Models\PageTemplate;
use Illuminate\Foundation\Http\FormRequest;

class StorePageTemplateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'names' => collect((array) $this->input('names', []))
                ->map(static function ($value): ?string {
                    $normalized = trim((string) $value);

                    return $normalized === '' ? null : $normalized;
                })
                ->all(),
            'slug' => ($normalized = mb_strtolower(trim((string) $this->input('slug', '')), 'UTF-8')) === ''
                ? null
                : $normalized,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'names' => ['required', 'array', 'min:1'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $activeLocaleCodes = Language::query()
                ->pluck('code')
                ->map(static fn ($code): string => (string) $code)
                ->values();

            $allowedLocales = $activeLocaleCodes->isNotEmpty()
                ? $activeLocaleCodes
                : collect([(string) app()->getLocale()]);

            $names = collect((array) $this->input('names', []));
            $slug = trim((string) $this->input('slug', ''));

            $invalidNameLocales = $names->keys()->filter(
                static fn ($locale): bool => ! $allowedLocales->contains((string) $locale)
            );
            if ($invalidNameLocales->isNotEmpty()) {
                $validator->errors()->add('names', __('Invalid locale keys were submitted.'));
            }

            $filledLocaleCount = 0;
            foreach ($allowedLocales as $locale) {
                $locale = (string) $locale;
                $name = trim((string) $names->get($locale, ''));

                if ($name !== '') {
                    $filledLocaleCount++;
                }
            }

            if ($filledLocaleCount === 0) {
                $validator->errors()->add('names', __('At least one locale name is required.'));
            }

            if ($slug !== '') {
                $exists = PageTemplate::query()
                    ->where('slug', $slug)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('slug', __('This slug is already used.'));
                }
            }
        });
    }
}
