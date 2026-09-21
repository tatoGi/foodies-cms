<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        $adminUserId = (int) (auth('admin')->id() ?? 0);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('admin_users', 'email')->ignore($adminUserId)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('This email is already used.'),
        ];
    }
}
