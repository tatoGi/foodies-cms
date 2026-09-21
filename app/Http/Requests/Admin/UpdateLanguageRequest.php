<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower(trim((string) $this->input('code', ''))),
            'country_code' => strtoupper(trim((string) $this->input('country_code', ''))),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Language|null $language */
        $language = $this->route('language');

        return [
            'name' => ['required', 'string', 'max:255'],
            'english_name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[a-z]{2}$/',
                Rule::unique('languages', 'code')->ignore($language?->id),
            ],
            'country_code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
            ],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
