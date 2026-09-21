<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class BootstrapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function localeCode(): ?string
    {
        return $this->validated('locale');
    }
}
