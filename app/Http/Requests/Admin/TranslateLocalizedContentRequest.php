<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TranslateLocalizedContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_locale' => ['required', 'string', 'max:10'],
            'source_locale' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var LanguageRepositoryInterface $languages */
            $languages = app(LanguageRepositoryInterface::class);
            $allowed = collect($languages->getActiveLocales())
                ->pluck('code')
                ->map(static fn ($code): string => strtolower((string) $code))
                ->values();

            $targetLocale = strtolower(trim((string) $this->input('target_locale', '')));
            $sourceLocale = strtolower(trim((string) $this->input('source_locale', '')));

            if ($targetLocale === '' || ! $allowed->contains($targetLocale)) {
                $validator->errors()->add('target_locale', __('Target locale must be an active language.'));
            }

            if ($sourceLocale !== '' && ! $allowed->contains($sourceLocale)) {
                $validator->errors()->add('source_locale', __('Source locale must be an active language.'));
            }

            if ($sourceLocale !== '' && $sourceLocale === $targetLocale) {
                $validator->errors()->add('source_locale', __('Source and target locales must be different.'));
            }
        });
    }
}
