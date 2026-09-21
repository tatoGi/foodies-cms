<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Menu|string|null $menu */
        $menu = $this->route('menu');
        $menuId = is_object($menu) ? (int) ($menu->id ?? 0) : (int) $menu;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('menus', 'slug')->ignore($menuId)],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.parent_key' => ['nullable', 'string', 'max:120'],
            'items.*.type' => ['required_with:items', 'string', Rule::in(['custom', 'page', 'post', 'program', 'product'])],
            'items.*.reference_id' => ['nullable', 'integer'],
            'items.*.url' => ['nullable', 'string', 'max:500'],
            'items.*.target' => ['nullable', 'string', Rule::in(['_self', '_blank'])],
            'items.*.order' => ['nullable', 'integer', 'min:0'],
            'items.*.labels' => ['nullable', 'array'],
            'items.*.labels.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
