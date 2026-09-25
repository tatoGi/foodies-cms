<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class WebsitePostListRequest extends FormRequest
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
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function localeCode(): string
    {
        return strtolower(trim((string) $this->query('locale', '')));
    }

    public function limitValue(): int
    {
        return $this->integer('limit', 12);
    }
}
