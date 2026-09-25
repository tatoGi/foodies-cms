<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['nullable', 'string', 'max:8'],
            'featured' => ['nullable', 'boolean'],
        ];
    }

    public function featuredOnly(): bool
    {
        return $this->boolean('featured');
    }

    public function localeCode(): string
    {
        return strtolower(trim((string) $this->query('locale', '')));
    }
}
