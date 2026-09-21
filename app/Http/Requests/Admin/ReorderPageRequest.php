<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordered_ids' => ['required_without:tree', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:pages,id'],
            'tree' => ['required_without:ordered_ids', 'array', 'min:1'],
            'tree.*.id' => ['required', 'integer', 'exists:pages,id'],
            'tree.*.children' => ['nullable', 'array'],
        ];
    }
}
