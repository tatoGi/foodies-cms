<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class PosMenuSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authenticated by AuthenticatePosDevice middleware
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'categories' => ['present', 'array'],
            'categories.*.external_id' => ['required', 'integer'],
            'categories.*.name' => ['required', 'array', 'min:1'],
            'categories.*.name.*' => ['required', 'string', 'max:255'],
            'categories.*.description' => ['sometimes', 'array'],
            'categories.*.description.*' => ['nullable', 'string'],
            'categories.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'categories.*.show_on_menu_board' => ['sometimes', 'boolean'],

            'items' => ['present', 'array'],
            'items.*.external_id' => ['required', 'integer'],
            'items.*.name' => ['required', 'array', 'min:1'],
            'items.*.name.*' => ['required', 'string', 'max:255'],
            'items.*.description' => ['sometimes', 'array'],
            'items.*.description.*' => ['nullable', 'string'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.is_available' => ['sometimes', 'boolean'],
            'items.*.show_on_menu_board' => ['sometimes', 'boolean'],
            'items.*.category_ids' => ['sometimes', 'array'],
            'items.*.category_ids.*' => ['integer'],
            'items.*.ingredients' => ['sometimes', 'array'],
            'items.*.ingredients.*.external_id' => ['required', 'integer'],
            'items.*.ingredients.*.name' => ['required', 'array', 'min:1'],
            'items.*.ingredients.*.is_removable' => ['sometimes', 'boolean'],
            'items.*.addons' => ['sometimes', 'array'],
            'items.*.addons.*.external_id' => ['required', 'integer'],
            'items.*.addons.*.name' => ['required', 'array', 'min:1'],
            'items.*.addons.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.image' => ['sometimes', 'nullable', 'array'],
            'items.*.image.mime' => ['required_with:items.*.image.data', 'string', 'in:image/jpeg,image/png,image/webp,image/gif'],
            'items.*.image.data' => ['required_with:items.*.image.mime', 'string', 'max:7000000'],
        ];
    }
}
