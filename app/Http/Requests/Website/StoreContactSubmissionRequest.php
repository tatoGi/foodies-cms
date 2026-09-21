<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactSubmissionRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
            'locale' => ['nullable', 'string', 'max:8'],
            'form_name' => ['nullable', 'string', 'max:100'],
            'page_slug' => ['nullable', 'string', 'max:255'],
            'page_url' => ['nullable', 'string', 'max:500'],
        ];
    }
}
