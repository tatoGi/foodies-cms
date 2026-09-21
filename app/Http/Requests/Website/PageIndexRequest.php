<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class PageIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'locale' => strtolower(trim((string) $this->query('locale', ''))),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['nullable', 'string', 'max:8'],
        ];
    }

    public function localeCode(): ?string
    {
        $locale = trim((string) $this->validated('locale', ''));

        return $locale !== '' ? $locale : null;
    }
}
