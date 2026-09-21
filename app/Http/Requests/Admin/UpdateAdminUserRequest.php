<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) optional($this->route('user'))->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('admin_users', 'email')->ignore($userId)],
            'role_id' => ['required', 'integer', Rule::exists('admin_roles', 'id')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => __('Please select a role.'),
            'password.confirmed' => __('Password confirmation does not match.'),
        ];
    }
}
