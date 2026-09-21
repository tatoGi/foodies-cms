<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class PageShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => trim((string) $this->route('slug')),
            'locale' => strtolower(trim((string) $this->query('locale', ''))),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:8'],
        ];
    }

    public function slug(): string
    {
        return (string) $this->validated('slug');
    }

    public function localeCode(): ?string
    {
        $locale = trim((string) $this->validated('locale', ''));

        return $locale !== '' ? $locale : null;
    }
}
