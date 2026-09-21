<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = (int) optional($this->route('role'))->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('admin_roles', 'name')->ignore($roleId)],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('admin_roles', 'slug')->ignore($roleId)],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('admin_permissions', 'id')],
            'permission_keys' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
