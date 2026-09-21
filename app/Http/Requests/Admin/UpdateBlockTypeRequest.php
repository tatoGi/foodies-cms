<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\BlockTypeDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlockTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $block = $this->route('block');
        $blockId = $block instanceof BlockTypeDefinition ? $block->id : $block;

        return [
            'key' => ['required', 'string', 'max:50', Rule::unique('block_type_definitions', 'key')->ignore($blockId)],
            'scope' => ['required', 'string', Rule::in(['page', 'post', 'product', 'global'])],
            'icon' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
            'labels' => ['required', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string'],
            'fields' => ['nullable', 'array'],
            'fields.*.key' => ['required', 'string', 'max:100'],
            'fields.*.type' => ['required', 'string', 'max:50'],
            'fields.*.labels' => ['nullable', 'array'],
            'fields.*.labels.*' => ['nullable', 'string', 'max:255'],
            'fields.*.helps' => ['nullable', 'array'],
            'fields.*.helps.*' => ['nullable', 'string', 'max:1000'],
            'fields.*.add_button_labels' => ['nullable', 'array'],
            'fields.*.add_button_labels.*' => ['nullable', 'string', 'max:255'],
            'fields.*.fields' => ['nullable', 'array'],
            'fields.*.fields.*.key' => ['required', 'string', 'max:100'],
            'fields.*.fields.*.type' => ['required', 'string', 'max:50'],
            'fields.*.fields.*.labels' => ['nullable', 'array'],
            'fields.*.fields.*.labels.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
