<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkLanguageActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['activate', 'deactivate'])],
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer', 'exists:languages,id'],
        ];
    }
}
