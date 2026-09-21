<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallRequestRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'max:8'],
            'page_slug' => ['nullable', 'string', 'max:255'],
            'page_url' => ['nullable', 'string', 'max:500'],
        ];
    }
}
